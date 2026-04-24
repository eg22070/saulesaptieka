<?php

namespace App\Http\Controllers;

use App\Models\Pasutijums;
use App\Models\PasutijumuPieprasijumaBrivaisArtikuls;
use App\Models\Pharmacy;
use App\Models\PasutijumuPieprasijums;
use App\Models\Product;
use App\Models\Requests;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\ComplexType\TblWidth;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class PasutijumuPieprasijumsController extends Controller
{
    protected function performComplete(PasutijumuPieprasijums $pieprasijums): void
    {
        DB::transaction(function () use ($pieprasijums) {
            $pasutijumi = Pasutijums::where('pieprasijuma_id', $pieprasijums->id)
                ->lockForUpdate()
                ->get();
            $brivieArtikuli = PasutijumuPieprasijumaBrivaisArtikuls::where('pieprasijuma_id', $pieprasijums->id)
                ->lockForUpdate()
                ->get();

            if ($pasutijumi->isEmpty() && $brivieArtikuli->isEmpty()) {
                throw ValidationException::withMessages([
                    'pieprasijums' => 'Nav pievienotu pasūtījumu vai brīvo artikulu, ko pabeigt.',
                ]);
            }

            $doctorCheckRows = $pasutijumi->concat($brivieArtikuli);
            $doctorCheckArtikuli = Product::query()
                ->whereIn('id', $doctorCheckRows->pluck('artikula_id')->filter()->unique()->values()->all())
                ->get(['id', 'nosaukums', 'without_arst', 'nemedikamenti'])
                ->keyBy('id');

            $doctorFieldErrors = [];
            foreach ($doctorCheckRows as $row) {
                $artikulaId = (int) ($row->artikula_id ?? 0);
                if (!$artikulaId) {
                    continue;
                }

                $artikuls = $doctorCheckArtikuli->get($artikulaId);
                if (!$artikuls) {
                    continue;
                }

                $requiresDoctorFields = !((bool) $artikuls->without_arst || (bool) $artikuls->nemedikamenti);
                if (!$requiresDoctorFields) {
                    continue;
                }

                $missing = [];
                if (trim((string) ($row->arstniecibas_iestade ?? '')) === '') {
                    $missing[] = 'ārstniecības iestāde';
                }
                if (trim((string) ($row->arsts ?? '')) === '') {
                    $missing[] = 'ārsts';
                }

                if (!empty($missing)) {
                    $rowType = ($row instanceof PasutijumuPieprasijumaBrivaisArtikuls) ? 'Brīvais artikuls' : 'Pasūtījums';
                    $doctorFieldErrors[] = $rowType.' #'.$row->id.' ('.$artikuls->nosaukums.') nav norādīts: '.implode(' un ', $missing).'.';
                }
            }

            if (!empty($doctorFieldErrors)) {
                throw ValidationException::withMessages([
                    'pieprasijums' => $doctorFieldErrors,
                ]);
            }

            Pasutijums::where('pieprasijuma_id', $pieprasijums->id)->update([
                'datums' => $pieprasijums->datums,
                'pasutijuma_datums' => $pieprasijums->datums,
                'statuss' => 'neizpildits',
            ]);

            $warehousePharmacyId = Pharmacy::query()
                ->where('id', 1314)
                ->value('id');

            if (!$warehousePharmacyId) {
                $warehousePharmacyId = Pharmacy::query()->orderBy('id')->value('id');
            }

            if (!$warehousePharmacyId) {
                throw ValidationException::withMessages([
                    'pieprasijums' => 'Nav pieejama neviena aptieka priekš "Pieprasījumi noliktavai".',
                ]);
            }

            $totalsByArtikuls = $pasutijumi
                ->concat($brivieArtikuli)
                ->filter(fn ($row) => !empty($row->artikula_id))
                ->groupBy('artikula_id')
                ->map(fn ($rows) => (float) $rows->sum('skaits'));

            $productNames = Product::query()
                ->whereIn('id', $totalsByArtikuls->keys()->all())
                ->pluck('nosaukums', 'id');

            $invalidTotals = [];
            foreach ($totalsByArtikuls as $artikulaId => $sumSkaits) {
                $rounded = round((float) $sumSkaits);
                if (abs(((float) $sumSkaits) - $rounded) > 0.000001) {
                    $needed = ceil((float) $sumSkaits) - (float) $sumSkaits;
                    $neededText = rtrim(rtrim(number_format($needed, 2, ',', ''), '0'), ',');
                    $invalidTotals[] = ($productNames[(int) $artikulaId] ?? ('Artikuls #'.$artikulaId))
                        .' nepieciešams vēl '.$neededText.' daudzums, lai var pabeigt pieprasījumu.';
                }
            }

            if (!empty($invalidTotals)) {
                throw ValidationException::withMessages([
                    'pieprasijums' => $invalidTotals,
                ]);
            }

            $aizliegumsByArtikuls = is_array($pieprasijums->aizliegums_by_artikuls)
                ? $pieprasijums->aizliegums_by_artikuls
                : [];

            foreach ($totalsByArtikuls as $artikulaId => $sumSkaits) {
                $totalDaudzums = max(1, (int) round((float) $sumSkaits));
                $generatedAt = now();
                $aizliegumsValue = $aizliegumsByArtikuls[(string) (int) $artikulaId] ?? 'Drīkst aizvietot';
                if (!in_array($aizliegumsValue, $this->availableAizliegumi(), true)) {
                    $aizliegumsValue = 'Drīkst aizvietot';
                }
                $requestPayload = [
                    'aptiekas_id' => $warehousePharmacyId,
                    'artikula_id' => (int) $artikulaId,
                    'daudzums' => $totalDaudzums,
                    'izrakstitais_daudzums' => null,
                    'pazinojuma_datums' => null,
                    'statuss' => null,
                    'aizliegums' => $aizliegumsValue,
                    'iepircejs' => null,
                    'piegades_datums' => null,
                    'piezimes' => 'Automātiski no pasūtījumu pieprasījuma #'.$pieprasijums->id,
                    'completed' => false,
                    'completed_at' => null,
                    'who_completed' => null,
                    'cito' => false,
                    'created_at' => $generatedAt,
                    'updated_at' => $generatedAt,
                ];

                // Keep generated request date aligned with completed pieprasijums date.
                if (Schema::hasColumn('pieprasijumi', 'datums')) {
                    $requestPayload['datums'] = $pieprasijums->datums;
                }
                if (Schema::hasColumn('pieprasijumi', 'pazinojuma_datums')) {
                    $requestPayload['pazinojuma_datums'] = optional($pieprasijums->datums)->format('d/m/Y');
                }

                $requestItem = new Requests();
                $requestItem->timestamps = false;
                $requestItem->forceFill($requestPayload);
                $requestItem->save();
            }

            $pieprasijums->update([
                'completed' => true,
                'completed_at' => now(),
                'who_completed' => auth()->id(),
            ]);
        });
    }

    protected function availableAizliegumi(): array
    {
        return [
            'Drīkst aizvietot',
            'Nedrīkst aizvietot',
            'NVD',
            'Stacionārs',
        ];
    }

    protected function normalizeAizliegumiByArtikuls(PasutijumuPieprasijums $pieprasijums, array $assignedArtikuliIds): array
    {
        $allowed = $this->availableAizliegumi();
        $existing = $pieprasijums->aizliegums_by_artikuls;
        $existing = is_array($existing) ? $existing : [];
        $normalized = [];

        foreach ($assignedArtikuliIds as $artikulaId) {
            $key = (string) (int) $artikulaId;
            $value = $existing[$key] ?? 'Drīkst aizvietot';
            if (!in_array($value, $allowed, true)) {
                $value = 'Drīkst aizvietot';
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    protected function shouldHideDoctorFieldsForArtikuls(?int $artikulaId): bool
    {
        if (!$artikulaId) {
            return false;
        }

        return Product::query()
            ->whereKey($artikulaId)
            ->where(function ($q) {
                $q->where('without_arst', true)
                    ->orWhere('nemedikamenti', true);
            })
            ->exists();
    }

    protected function ensureBrivibasRole(): void
    {
        if (strtolower(auth()->user()->role ?? '') !== 'brivibas') {
            abort(403);
        }
    }

    protected function collectLinkedArtikuliIds(PasutijumuPieprasijums $pieprasijums): array
    {
        $fromPasutijumi = Pasutijums::query()
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->whereNotNull('artikula_id')
            ->pluck('artikula_id')
            ->map(fn ($id) => (int) $id);

        $fromBrivie = PasutijumuPieprasijumaBrivaisArtikuls::query()
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->whereNotNull('artikula_id')
            ->pluck('artikula_id')
            ->map(fn ($id) => (int) $id);

        return $fromPasutijumi
            ->concat($fromBrivie)
            ->unique()
            ->values()
            ->all();
    }

    public function index()
    {
        $this->ensureBrivibasRole();

        $pieprasijumi = PasutijumuPieprasijums::withCount('pasutijumi')
            ->with(['creator', 'completer'])
            ->orderByDesc('datums')
            ->orderByDesc('id')
            ->paginate(30);

        return view('pasutijumu-pieprasijumi.index', compact('pieprasijumi'));
    }

    public function store(Request $request)
    {
        $this->ensureBrivibasRole();

        $data = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
        ]);

        PasutijumuPieprasijums::create([
            'datums' => Carbon::createFromFormat('d/m/Y', $data['datums'])->toDateString(),
            'created_by' => auth()->id(),
            'completed' => false,
        ]);

        return redirect()->route('pasutijumu-pieprasijumi.index')
            ->with('success', 'Pieprasījums izveidots.');
    }

    public function show(PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        $pieprasijums->load(['creator', 'completer']);

        $assignedPasutijumi = Pasutijums::with('product')
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->orderByDesc('id')
            ->get();
        $brivieArtikuli = PasutijumuPieprasijumaBrivaisArtikuls::with('product')
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->orderByDesc('id')
            ->get();

        $availablePasutijumi = Pasutijums::with('product')
            ->whereNull('pieprasijuma_id')
            ->where('statuss', 'neapstradats')
            ->orderByDesc('id')
            ->paginate(50, ['*'], 'available_page');

        $artikuli = Product::query()->orderBy('nosaukums')->get();

        return view('pasutijumu-pieprasijumi.show', compact(
            'pieprasijums',
            'assignedPasutijumi',
            'brivieArtikuli',
            'availablePasutijumi',
            'artikuli'
        ));
    }

    protected function formatQuantity(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    protected function extractValstsFromNosaukums(string $artikulsName, ?string $fallbackValsts = null): string
    {
        $normalizedName = trim($artikulsName);
        if ($normalizedName === '') {
            return trim((string) $fallbackValsts) ?: '—';
        }

        if (preg_match('/\bPZN\s*\d+\s*$/i', $normalizedName)) {
            return 'DE';
        }

        $parts = preg_split('/\s+/', $normalizedName);
        $lastPart = is_array($parts) && !empty($parts) ? end($parts) : '';
        $lastPart = strtoupper(trim((string) $lastPart, " \t\n\r\0\x0B,.;:()[]"));
        if ($lastPart !== '') {
            return $lastPart;
        }

        return trim((string) $fallbackValsts) ?: '—';
    }

    protected function buildHierarchyForExport(Collection $assignedPasutijumi, Collection $brivieArtikuli): array
    {
        $allHierarchyRows = $assignedPasutijumi
            ->map(function ($p) {
                $p->entry_type = 'pasutijums';
                return $p;
            })
            ->concat(
                $brivieArtikuli->map(function ($row) {
                    $row->entry_type = 'brivais';
                    return $row;
                })
            );

        $hierarchy = $allHierarchyRows
            ->groupBy(function ($p) {
                if (!empty($p->artikula_id)) {
                    return 'a:'.$p->artikula_id;
                }
                return 'f:';
            })
            ->map(function ($rows) {
                $first = $rows->first();
                $artikulsName = $first->product?->nosaukums ?? '—';
                $doctorGroups = $rows
                    ->groupBy(function ($row) {
                        $institution = trim((string) ($row->arstniecibas_iestade ?? ''));
                        $doctor = trim((string) ($row->arsts ?? ''));
                        return strtolower($institution).'||'.strtolower($doctor);
                    })
                    ->map(function ($doctorRows) {
                        $firstRow = $doctorRows->first();
                        return [
                            'iestade' => trim((string) ($firstRow->arstniecibas_iestade ?? '')) ?: 'Nav iestādes',
                            'arsts' => trim((string) ($firstRow->arsts ?? '')) ?: 'Nav ārsta',
                            'sum' => (float) $doctorRows->sum('skaits'),
                        ];
                    })
                    ->values();

                return [
                    'artikula_id' => !empty($first->artikula_id) ? (int) $first->artikula_id : null,
                    'artikuls' => $artikulsName,
                    'sum' => (float) $rows->sum('skaits'),
                    'doctorGroups' => $doctorGroups,
                    'without_arst' => (bool) ($first->product?->without_arst ?? false),
                    'nemedikamenti' => (bool) ($first->product?->nemedikamenti ?? false),
                    'id_numurs' => trim((string) ($first->product?->id_numurs ?? '')),
                    'valsts' => $this->extractValstsFromNosaukums(
                        (string) $artikulsName,
                        (string) ($first->product?->valsts ?? '')
                    ),
                ];
            })
            ->sortBy('artikuls')
            ->values();

        $hierarchyNemedikamenti = $hierarchy
            ->filter(fn ($item) => (bool) ($item['nemedikamenti'] ?? false))
            ->values();

        $hierarchyNWithoutArst = $hierarchy
            ->filter(function ($item) {
                if ((bool) ($item['nemedikamenti'] ?? false)) {
                    return false;
                }
                $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                return str_starts_with($idNumurs, 'N') && (bool) ($item['without_arst'] ?? false);
            })
            ->values();

        $hierarchyEU = $hierarchy
            ->filter(function ($item) {
                if ((bool) ($item['nemedikamenti'] ?? false)) {
                    return false;
                }
                $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                return str_starts_with($idNumurs, 'EU');
            })
            ->values();

        $hierarchyNWithArst = $hierarchy
            ->filter(function ($item) {
                if ((bool) ($item['nemedikamenti'] ?? false)) {
                    return false;
                }
                $idNumurs = strtoupper(trim((string) ($item['id_numurs'] ?? '')));
                return str_starts_with($idNumurs, 'N') && !(bool) ($item['without_arst'] ?? false);
            })
            ->values();

        $detailedSections = collect([
            [
                'title' => 'CENTRALIZĒTI REĢISTRĒTU ZĀĻU SAŅEMŠANAI',
                'intro' => 'Lūdzu pasūtīt (EU):',
                'intro_break_after_lines' => 1,
                'items' => $hierarchyEU,
            ],
            [
                'title' => "NEREĢISTRĒTU BEZRECEPŠU UN\nNEREĢISTRĒTU ZĀĻU SAŅEMŠANAI VAI LATVIJAS ZĀĻU REĢISTRĀ REĢISTRĒTU ZĀĻU SAŅEMŠANAI",
                'intro' => 'Lūdzu pasūtīt. Ja Latvijā ir pieejams analogs, bet ir pamatojums uz receptes, ka neder LV pieejamie analogi:',
                'intro_break_after_lines' => 1,
                'items' => $hierarchyNWithArst,
            ],
        ])->filter(fn ($section) => $section['items']->isNotEmpty())->values();

        return [
            'nemedikamenti' => $hierarchyNemedikamenti,
            'n_without_arst' => $hierarchyNWithoutArst,
            'detailed_sections' => $detailedSections,
        ];
    }

    public function exportWord(PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();
        $pieprasijums->load(['creator', 'completer']);

        $assignedPasutijumi = Pasutijums::with('product')
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->orderByDesc('id')
            ->get();
        $brivieArtikuli = PasutijumuPieprasijumaBrivaisArtikuls::with('product')
            ->where('pieprasijuma_id', $pieprasijums->id)
            ->orderByDesc('id')
            ->get();

        $hierarchy = $this->buildHierarchyForExport($assignedPasutijumi, $brivieArtikuli);
        $aizliegumsByArtikuls = is_array($pieprasijums->aizliegums_by_artikuls)
            ? $pieprasijums->aizliegums_by_artikuls
            : [];

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        $phpWord->addNumberingStyle('nemedikamenti_list', [
            'type' => 'multilevel',
            'levels' => [
                [
                    'format' => 'decimal',
                    'text' => '%1.',
                    // 1.27 cm list indent, 0.63 cm number position.
                    // In twips: left=720, number position = left-hanging = 360.
                    'left' => 720,
                    'hanging' => 360,
                    'tabPos' => 720,
                ],
            ],
        ]);
        $phpWord->addTableStyle(
            'export_table',
            [
                'borderSize' => 6,
                'borderColor' => '000000',
                // 0.19 cm ~= 108 twips.
                'cellMarginLeft' => 108,
                'cellMarginRight' => 108,
            ],
            ['bgColor' => 'D9D9D9']
        );
        $phpWord->addTableStyle(
            'export_item_heading',
            [
                'borderSize' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]
        );
        $phpWord->addTableStyle(
            'export_item_table',
            [
                'borderSize' => 0,
                // Keep exact column widths (disable auto-resize behavior).
                'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                // 0.19 cm ~= 108 twips.
                'cellMarginLeft' => 108,
                'cellMarginRight' => 108,
                'borderInsideHSize' => 0,
                'borderInsideVSize' => 0,
            ]
        );
        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.0,
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);

        $section = $phpWord->addSection([
            // 2.54 cm margins on all sides.
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);
        $right = ['alignment' => 'right', 'lineHeight' => 1.0, 'spaceAfter' => 0, 'spaceBefore' => 0];
        $left = ['alignment' => 'left', 'lineHeight' => 1.0, 'spaceAfter' => 0, 'spaceBefore' => 0];
        $center = ['alignment' => 'center', 'lineHeight' => 1.0, 'spaceAfter' => 0, 'spaceBefore' => 0];
        $dateText = $pieprasijums->datums
            ? $pieprasijums->datums->copy()->locale('lv')->translatedFormat('Y').'.gada '.$pieprasijums->datums->format('d').'. '.$pieprasijums->datums->copy()->locale('lv')->translatedFormat('F')
            : '';

        $addDocumentHeader = function () use ($section, $right, $left, $dateText) {
            $section->addText('SIA „Saules aptieka”', [], $right);
            $section->addText('zāļu lieltirgotavas', [], $right);
            $section->addText('atbildīgajai amatpersonai', [], $right);
            $section->addTextBreak(2);
            $section->addText('Saules aptiekas – 10', [], $right);
            $section->addText('Licences Nr. A00098', [], $right);
            $section->addText('Tālr.: +371 67506927', [], $right);
            $section->addTextBreak(1);
            if ($dateText !== '') {
                $section->addText($dateText, [], $left);
            }
            $section->addTextBreak(1);
        };

        $addSectionHeading = function (string $documentType, string $sectionName) use ($section, $center) {
            $section->addText($documentType, ['bold' => true], $center);
            foreach (preg_split('/\r\n|\r|\n/', $sectionName) as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $section->addText($line, ['bold' => true], $center);
                }
            }
            $section->addText('Rīgā', [], $center);
            $section->addTextBreak(1);
        };

        $addSignature = function (int $topSpacingLines = 1) use ($section, $right) {
            $section->addTextBreak(max(0, $topSpacingLines));
            $section->addText('Saules aptiekas – 10 vadītāja', [], $right);
            $section->addText('A.Pozņaka', [], $right);
        };

        $hasPrintedSection = false;

        if ($hierarchy['nemedikamenti']->isNotEmpty()) {
            $addDocumentHeader();
            $addSectionHeading('PASŪTĪJUMS', 'NEREĢISTRĒTU NE -ZĀĻU SAŅEMŠANAI');
            $section->addText('Lūdzu piegādāt, saskaņā ar Farmācijas likuma 10.panta 7a punktu:');
            $section->addTextBreak(1);
            $section->addText('Pēc klientu iesniegtām receptēm:');
            $section->addTextBreak(1);

            foreach ($hierarchy['nemedikamenti']->values() as $item) {
                $listItemRun = $section->addListItemRun(
                    0,
                    'nemedikamenti_list',
                    $left
                );
                $listItemRun->addText((string) $item['artikuls'], ['bold' => true]);
                $listItemRun->addText(' - '.$this->formatQuantity((float) $item['sum']).' oriģināli');
            }

            $addSignature(7);
            $hasPrintedSection = true;
        }

        if ($hierarchy['n_without_arst']->isNotEmpty()) {
            if ($hasPrintedSection) {
                $section->addPageBreak();
            }
            $addDocumentHeader();
            $addSectionHeading('PAZIŅOJUMS', 'NEREĢISTRĒTU ZĀĻU SAŅEMŠANAI');
            $section->addText('Lūdzu piegādāt, saskaņā ar Farmācijas likuma 10.panta 7a punktu (paziņojums):');
            $section->addTextBreak(1);

            $groupedByValsts = $hierarchy['n_without_arst']
                ->groupBy(fn ($item) => (string) ($item['valsts'] ?? '—'))
                ->sortKeys();

            $table = $section->addTable('export_table');
            $table->addRow();
            // Requested widths: 1.99 cm, 11.88 cm, 2.03 cm.
            $valstsWidth = 1128;
            $nosaukumsWidth = 6735;
            $skaitsWidth = 1151;
            $headerCellStyle = ['bgColor' => 'D9D9D9'];
            $countryRowCellStyle = ['bgColor' => 'F2F2F2'];

            $table->addCell($valstsWidth, $headerCellStyle)->addText('VALSTS', ['bold' => true], $center);
            $table->addCell($nosaukumsWidth, $headerCellStyle)->addText('ZĀĻU NOSAUKUMS', ['bold' => true], $center);
            $table->addCell($skaitsWidth, $headerCellStyle)->addText('SKAITS', ['bold' => true], $center);

            foreach ($groupedByValsts as $valsts => $items) {
                $table->addRow();
                $table->addCell($valstsWidth, $countryRowCellStyle)->addText((string) $valsts);
                $table->addCell($nosaukumsWidth, $countryRowCellStyle)->addText('');
                $table->addCell($skaitsWidth, $countryRowCellStyle)->addText('');

                foreach ($items as $item) {
                    $table->addRow();
                    $table->addCell($valstsWidth)->addText('');
                    $table->addCell($nosaukumsWidth)->addText((string) $item['artikuls'], ['bold' => true]);
                    $table->addCell($skaitsWidth)->addText($this->formatQuantity((float) $item['sum']));
                }
            }

            $addSignature(3);
            $hasPrintedSection = true;
        }

        foreach ($hierarchy['detailed_sections'] as $sectionData) {
            if ($hasPrintedSection) {
                $section->addPageBreak();
            }
            $addDocumentHeader();
            $addSectionHeading('PIEPRASĪJUMS', (string) $sectionData['title']);
            $section->addText((string) $sectionData['intro']);
            $introBreakLines = (int) ($sectionData['intro_break_after_lines'] ?? 0);
            if ($introBreakLines > 0) {
                $section->addTextBreak($introBreakLines);
            }

            foreach ($sectionData['items']->values() as $idx => $item) {
                // Requested column widths for sections 3/4:
                // Target widths:
                // 6.76 cm, 6.13 cm, 3.3 cm.
                $iestadeWidth = 3833;
                $arstsWidth = 3476;
                $daudzWidth = 1871;

                // Build one table per item to keep exact row width/alignment.
                $itemTable = $section->addTable([
                    'borderSize' => 0,
                    'layout' => \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED,
                    'indent' => new TblWidth(-147),
                    'cellMarginLeft' => 108,
                    'cellMarginRight' => 108,
                    'borderInsideHSize' => 0,
                    'borderInsideVSize' => 0,
                    'columnWidths' => [$iestadeWidth, $arstsWidth, $daudzWidth],
                ]);
                $itemTable->addRow();
                $headingRowCellStyle = [
                    'gridSpan' => 2,
                    // Visually keep only bottom border for this row.
                    // Use white top/left/right borders to prevent Word defaults.
                    'borderTopSize' => 6,
                    'borderTopColor' => 'FFFFFF',
                    'borderLeftSize' => 6,
                    'borderLeftColor' => 'FFFFFF',
                    'borderRightSize' => 6,
                    'borderRightColor' => 'FFFFFF',
                    'borderBottomSize' => 6,
                    'borderBottomColor' => '000000',
                ];
                $headingRightCellStyle = [
                    // Visually keep only bottom border for this row.
                    'borderTopSize' => 6,
                    'borderTopColor' => 'FFFFFF',
                    'borderLeftSize' => 6,
                    'borderLeftColor' => 'FFFFFF',
                    'borderRightSize' => 6,
                    'borderRightColor' => 'FFFFFF',
                    'borderBottomSize' => 6,
                    'borderBottomColor' => '000000',
                ];

                $nameCell = $itemTable->addCell($iestadeWidth + $arstsWidth, $headingRowCellStyle);
                $nameRun = $nameCell->addTextRun([
                    'alignment' => 'left',
                    'lineHeight' => 1.0,
                    'spaceAfter' => 0,
                    'spaceBefore' => 0,
                    // Hanging indent 3.47 cm for wrapped "Zāļu nosaukums" text.
                    'indentation' => [
                        'left' => 1967,
                        'hanging' => 1967,
                    ],
                ]);
                $nameRun->addText(($idx + 1).'. Zāļu nosaukums: ');
                $nameRun->addText((string) $item['artikuls'], ['bold' => true]);

                $kopaCell = $itemTable->addCell($daudzWidth, $headingRightCellStyle);
                $kopaRun = $kopaCell->addTextRun(['alignment' => 'left', 'lineHeight' => 1.0, 'spaceAfter' => 0, 'spaceBefore' => 0]);
                $kopaRun->addText('KOPĀ: ', ['bold' => true]);
                $kopaRun->addText($this->formatQuantity((float) $item['sum']));

                $headerRowCellStyle = [
                    'bgColor' => 'D9D9D9',
                    // This header row must have full borders.
                    'borderTopSize' => 6,
                    'borderBottomSize' => 6,
                    'borderLeftSize' => 6,
                    'borderRightSize' => 6,
                    'borderColor' => '000000',
                ];
                $itemTable->addRow();
                $itemTable->addCell($iestadeWidth, $headerRowCellStyle)->addText('Ārstniecības iestādes nosaukums', [], $left);
                $itemTable->addCell($arstsWidth, $headerRowCellStyle)->addText('Ārsta vārds, uzvārds', [], $left);
                $itemTable->addCell($daudzWidth, $headerRowCellStyle)->addText('Daudz.', [], $center);

                $doctorGroups = collect($item['doctorGroups'])->values();
                foreach ($doctorGroups as $rowIdx => $doctorGroup) {
                    $isLastRow = $rowIdx === ($doctorGroups->count() - 1);
                    $dataCellStyle = [
                        // Keep only vertical borders for inner rows.
                        // Word may ignore 0-size border settings and fall back to defaults,
                        // so use explicit white top/bottom borders to suppress inner lines.
                        'borderSize' => 0,
                        'borderTopSize' => $isLastRow ? 0 : 6,
                        'borderTopColor' => $isLastRow ? '000000' : 'FFFFFF',
                        'borderBottomSize' => $isLastRow ? 6 : 6,
                        'borderBottomColor' => $isLastRow ? '000000' : 'FFFFFF',
                        'borderLeftSize' => 6,
                        'borderLeftColor' => '000000',
                        'borderRightSize' => 6,
                        'borderRightColor' => '000000',
                    ];
                    $itemTable->addRow();
                    $itemTable->addCell($iestadeWidth, $dataCellStyle)->addText((string) $doctorGroup['iestade']);
                    $itemTable->addCell($arstsWidth, $dataCellStyle)->addText((string) $doctorGroup['arsts']);
                    $itemTable->addCell($daudzWidth, $dataCellStyle)->addText(
                        $this->formatQuantity((float) $doctorGroup['sum']),
                        [],
                        $center
                    );
                }

                $artikulaId = isset($item['artikula_id']) ? (int) $item['artikula_id'] : 0;
                $aizliegumsValue = $artikulaId > 0
                    ? (string) ($aizliegumsByArtikuls[(string) $artikulaId] ?? 'Drīkst aizvietot')
                    : 'Drīkst aizvietot';

                if (in_array($aizliegumsValue, ['Nedrīkst aizvietot', 'NVD'], true)) {
                    $itemTable->addRow();
                    $noteCellStyle = [
                        'gridSpan' => 3,
                        'bgColor' => 'FFF2CC', // Gold, Lighter 80%
                        'borderTopSize' => 6,
                        'borderBottomSize' => 6,
                        'borderLeftSize' => 6,
                        'borderRightSize' => 6,
                        'borderColor' => '000000',
                    ];
                    $noteCell = $itemTable->addCell($iestadeWidth + $arstsWidth + $daudzWidth, $noteCellStyle);

                    if ($aizliegumsValue === 'Nedrīkst aizvietot') {
                        $noteRun = $noteCell->addTextRun($left);
                        $noteRun->addText('“zāles atļauts aizvietot”');
                        $noteRun->addText(' un ir aizlieguma pamatojums');
                    } else {
                        $noteCell->addText(
                            'Zāles nepieciešamas pacientiem pamatojoties uz NVD individuālās kompensācijas līgumiem',
                            [],
                            $left
                        );
                    }
                }
                $section->addTextBreak(2);
            }

            $addSignature(3);
            $hasPrintedSection = true;
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'pieprasijums_docx_');
        if ($tempPath === false) {
            abort(500, 'Neizdevās sagatavot Word failu.');
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempPath);

        $filename = 'Pieprasijums_'.$pieprasijums->id.'_'.optional($pieprasijums->datums)->format('Ymd').'_'.now()->format('His').'.docx';

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function update(Request $request, PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['datums' => 'Pabeigtu pieprasījumu vairs nevar labot.']);
        }

        $data = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
            'aizliegums_by_artikuls' => 'nullable|array',
            'aizliegums_by_artikuls.*' => 'nullable|string|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
        ]);

        $assignedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);
        $inputMap = $data['aizliegums_by_artikuls'] ?? [];
        $existingMap = is_array($pieprasijums->aizliegums_by_artikuls)
            ? $pieprasijums->aizliegums_by_artikuls
            : [];
        $selectedAizliegumi = [];

        foreach ($assignedArtikuliIds as $artikulaId) {
            $key = (string) (int) $artikulaId;
            $value = $inputMap[$key] ?? ($existingMap[$key] ?? 'Drīkst aizvietot');
            if (!in_array($value, $this->availableAizliegumi(), true)) {
                $value = 'Drīkst aizvietot';
            }
            $selectedAizliegumi[$key] = $value;
        }

        $pieprasijums->update([
            'datums' => Carbon::createFromFormat('d/m/Y', $data['datums'])->toDateString(),
            'aizliegums_by_artikuls' => $selectedAizliegumi,
        ]);

        if ($request->boolean('complete_after_save')) {
            $this->performComplete($pieprasijums->fresh());

            return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
                ->with('success', 'Pieprasījums pabeigts un izveidoti ieraksti sadaļā "Pieprasījumi noliktavai".');
        }

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Pieprasījuma dati saglabāti.');
    }

    public function destroy(PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['pieprasijums' => 'Pabeigtu pieprasījumu nevar dzēst.']);
        }

        Pasutijums::where('pieprasijuma_id', $pieprasijums->id)->update(['pieprasijuma_id' => null]);
        $pieprasijums->delete();

        return redirect()->route('pasutijumu-pieprasijumi.index')
            ->with('success', 'Pieprasījums izdzēsts.');
    }

    public function syncPasutijumi(Request $request, PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['pasutijumi' => 'Pabeigtam pieprasījumam pasūtījumus mainīt nevar.']);
        }

        $selectedIds = collect($request->input('pasutijumi', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        DB::transaction(function () use ($pieprasijums, $selectedIds) {
            Pasutijums::where('pieprasijuma_id', $pieprasijums->id)
                ->whereNotIn('id', $selectedIds)
                ->update(['pieprasijuma_id' => null]);

            if ($selectedIds->isNotEmpty()) {
                Pasutijums::whereIn('id', $selectedIds)
                    ->whereNull('pieprasijuma_id')
                    ->where('statuss', 'neapstradats')
                    ->update(['pieprasijuma_id' => $pieprasijums->id]);
            }

            $assignedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);

            $pieprasijums->update([
                'aizliegums_by_artikuls' => $this->normalizeAizliegumiByArtikuls($pieprasijums, $assignedArtikuliIds),
            ]);
        });

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Pasūtījumu saraksts saglabāts.');
    }

    public function updateAizliegumi(Request $request, PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['aizliegumi' => 'Pabeigtam pieprasījumam aizliegumus mainīt nevar.']);
        }

        $data = $request->validate([
            'aizliegums_by_artikuls' => 'nullable|array',
            'aizliegums_by_artikuls.*' => 'nullable|string|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
        ]);

        $assignedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);

        $inputMap = $data['aizliegums_by_artikuls'] ?? [];
        $existingMap = is_array($pieprasijums->aizliegums_by_artikuls)
            ? $pieprasijums->aizliegums_by_artikuls
            : [];
        $selected = [];
        foreach ($assignedArtikuliIds as $artikulaId) {
            $key = (string) (int) $artikulaId;
            $value = $inputMap[$key] ?? ($existingMap[$key] ?? 'Drīkst aizvietot');
            if (!in_array($value, $this->availableAizliegumi(), true)) {
                $value = 'Drīkst aizvietot';
            }
            $selected[$key] = $value;
        }

        $pieprasijums->update([
            'aizliegums_by_artikuls' => $selected,
        ]);

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Aizliegumi saglabāti.');
    }

    public function storeBrivaisArtikuls(Request $request, PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['brivais_artikuls' => 'Pabeigtā pieprasījumā brīvos artikulus pievienot nevar.']);
        }

        $data = $request->validate([
            'skaits' => 'required|numeric|min:0.01',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'artikula_id' => 'required|exists:artikuli,id',
        ]);

        $artikulaId = (int) $data['artikula_id'];

        $payload = [
            'pieprasijuma_id' => $pieprasijums->id,
            'artikula_id' => $artikulaId,
            'skaits' => (float) $data['skaits'],
            'arstniecibas_iestade' => trim((string) ($data['arstniecibas_iestade'] ?? '')) ?: null,
            'arsts' => trim((string) ($data['arsts'] ?? '')) ?: null,
            'created_by' => auth()->id(),
        ];

        if ($this->shouldHideDoctorFieldsForArtikuls($artikulaId)) {
            $payload['arstniecibas_iestade'] = null;
            $payload['arsts'] = null;
        }

        DB::transaction(function () use ($payload, $pieprasijums) {
            PasutijumuPieprasijumaBrivaisArtikuls::create($payload);
            $linkedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);
            $pieprasijums->update([
                'aizliegums_by_artikuls' => $this->normalizeAizliegumiByArtikuls($pieprasijums, $linkedArtikuliIds),
            ]);
        });

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Brīvais artikuls pievienots.');
    }

    public function updateBrivaisArtikuls(Request $request, PasutijumuPieprasijums $pieprasijums, PasutijumuPieprasijumaBrivaisArtikuls $brivaisArtikuls)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['brivais_artikuls' => 'Pabeigtā pieprasījumā brīvos artikulus labot nevar.']);
        }

        if ((int) $brivaisArtikuls->pieprasijuma_id !== (int) $pieprasijums->id) {
            abort(404);
        }

        $data = $request->validate([
            'skaits' => 'required|numeric|min:0.01',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'artikula_id' => 'required|exists:artikuli,id',
        ]);

        $artikulaId = (int) $data['artikula_id'];
        $payload = [
            'artikula_id' => $artikulaId,
            'skaits' => (float) $data['skaits'],
            'arstniecibas_iestade' => trim((string) ($data['arstniecibas_iestade'] ?? '')) ?: null,
            'arsts' => trim((string) ($data['arsts'] ?? '')) ?: null,
        ];

        if ($this->shouldHideDoctorFieldsForArtikuls($artikulaId)) {
            $payload['arstniecibas_iestade'] = null;
            $payload['arsts'] = null;
        }

        DB::transaction(function () use ($brivaisArtikuls, $payload, $pieprasijums) {
            $brivaisArtikuls->update($payload);
            $linkedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);
            $pieprasijums->update([
                'aizliegums_by_artikuls' => $this->normalizeAizliegumiByArtikuls($pieprasijums, $linkedArtikuliIds),
            ]);
        });

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Brīvais artikuls atjaunināts.');
    }

    public function destroyBrivaisArtikuls(PasutijumuPieprasijums $pieprasijums, PasutijumuPieprasijumaBrivaisArtikuls $brivaisArtikuls)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['brivais_artikuls' => 'Pabeigtā pieprasījumā brīvos artikulus dzēst nevar.']);
        }

        if ((int) $brivaisArtikuls->pieprasijuma_id !== (int) $pieprasijums->id) {
            abort(404);
        }

        DB::transaction(function () use ($brivaisArtikuls, $pieprasijums) {
            $brivaisArtikuls->delete();
            $linkedArtikuliIds = $this->collectLinkedArtikuliIds($pieprasijums);
            $pieprasijums->update([
                'aizliegums_by_artikuls' => $this->normalizeAizliegumiByArtikuls($pieprasijums, $linkedArtikuliIds),
            ]);
        });

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Brīvais artikuls izdzēsts.');
    }

    public function updatePasutijums(Request $request, PasutijumuPieprasijums $pieprasijums, Pasutijums $pasutijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['pasutijums' => 'Pabeigtā pieprasījumā pasūtījumus labot nevar.']);
        }

        if (!in_array($pasutijums->pieprasijuma_id, [null, $pieprasijums->id], true)) {
            abort(404);
        }

        $data = $request->validate([
            'skaits' => 'required|numeric|min:0.01',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'artikula_id' => 'nullable|exists:artikuli,id',
            'farmaceita_nosaukums' => 'nullable|string|max:512',
        ]);

        $artikulaId = !empty($data['artikula_id']) ? (int) $data['artikula_id'] : null;
        $farmaceitaNosaukums = trim((string) ($data['farmaceita_nosaukums'] ?? ''));

        if (!$artikulaId && $farmaceitaNosaukums === '') {
            return redirect()->back()->withErrors([
                'pasutijums' => 'Norādiet artikulu no saraksta vai brīvās formas zāļu nosaukumu.',
            ]);
        }

        $updatePayload = [
            'skaits' => (float) $data['skaits'],
            'arstniecibas_iestade' => trim((string) ($data['arstniecibas_iestade'] ?? '')) ?: null,
            'arsts' => trim((string) ($data['arsts'] ?? '')) ?: null,
            'artikula_id' => $artikulaId,
            'farmaceita_nosaukums' => $artikulaId ? null : $farmaceitaNosaukums,
        ];

        if ($this->shouldHideDoctorFieldsForArtikuls($artikulaId)) {
            $updatePayload['arstniecibas_iestade'] = null;
            $updatePayload['arsts'] = null;
        }

        $pasutijums->update($updatePayload);

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Pasūtījums atjaunināts.');
    }

    public function complete(PasutijumuPieprasijums $pieprasijums)
    {
        $this->ensureBrivibasRole();

        if ($pieprasijums->completed) {
            return redirect()->back()->withErrors(['pieprasijums' => 'Pieprasījums jau ir pabeigts.']);
        }

        $this->performComplete($pieprasijums);

        return redirect()->route('pasutijumu-pieprasijumi.show', $pieprasijums)
            ->with('success', 'Pieprasījums pabeigts un izveidoti ieraksti sadaļā "Pieprasījumi noliktavai".');
    }
}
