<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requests;
use App\Models\Pharmacy;  // Assuming such model exists
use App\Models\Product;
use App\Models\DeleteHistory;
use App\Models\Pasutijums;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RequestController extends Controller
{
    protected function extractPasutijumaPieprasijumaId(?string $piezimes): ?int
    {
        if (!$piezimes) {
            return null;
        }

        if (preg_match('/pasūtījumu pieprasījuma\s*#\s*(\d+)/iu', $piezimes, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/pasutijumu pieprasijuma\s*#\s*(\d+)/iu', $piezimes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function syncLinkedPasutijumiArtikuls(int $pieprasijumaId, int $oldArtikulaId, int $newArtikulaId): void
    {
        if ($oldArtikulaId === $newArtikulaId) {
            return;
        }

        $linkedPasutijumi = Pasutijums::query()
            ->where('pieprasijuma_id', $pieprasijumaId)
            ->where('artikula_id', $oldArtikulaId)
            ->get();

        foreach ($linkedPasutijumi as $pasutijums) {
            $history = is_array($pasutijums->previous_artikuli_ids) ? $pasutijums->previous_artikuli_ids : [];
            if (!in_array($oldArtikulaId, $history)) {
                $history[] = $oldArtikulaId;
            }

            $pasutijums->artikula_id = $newArtikulaId;
            $pasutijums->farmaceita_nosaukums = null;
            $pasutijums->previous_artikuli_ids = array_values(array_unique($history));
            $pasutijums->save();
        }
    }

    protected function artikuliForCurrentUser()
    {
        $query = Product::query();
        $role = strtolower(auth()->user()->role ?? '');

        if ($role === 'kruzes') {
            $query->where('hide_from_kruzes', false);
        }

        return $query->get();
    }

    public function index(Request $request)
    {
        if (auth()->check() && strtolower(auth()->user()->role) === 'farmaceiti') {
            abort(403);
        }

        $query = Requests::with(['aptiekas', 'artikuli']);
        $status_filter = $request->has('status_filter')
        ? $request->input('status_filter')
        : 'incomplete';
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->whereHas('aptiekas', function($q) use ($searchTerm) {
                    $q->where('nosaukums', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('artikuli', function($q) use ($searchTerm) {
                    $q->where('nosaukums', 'like', "%{$searchTerm}%");
                })
                ->orWhere('iepircejs', 'like', "%{$searchTerm}%");
            });
        }
        if ($status_filter === 'completed') {
            $query->where('completed', true);
        } elseif ($status_filter === 'incomplete') {
            $query->where('completed', false);
        }
        // Pharmacy filter
        if ($pharmacyFilter = $request->input('pharmacy_filter')) {
            if ($pharmacyFilter === 'saule10') {
                // Only Saule-10
                $query->where('aptiekas_id', 1314);
            } elseif ($pharmacyFilter === 'other') {
                // All pharmacies except Saule-10
                $query->where('aptiekas_id', '!=', 1314);
            }
        }

        // Buyer (Iepircējs) filter
        if ($buyerFilter = $request->input('buyer_filter')) {
            if ($buyerFilter === 'no_buyer') {
                // Show records where iepircejs is empty / null
                $query->where(function ($q) {
                    $q->whereNull('iepircejs')
                    ->orWhere('iepircejs', '');
                });
            } else {
                $query->where('iepircejs', $buyerFilter);
            }
        }
        // date range filter
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        if ($dateFrom && $dateTo) {
            try {
                $from = \Carbon\Carbon::createFromFormat('d/m/Y', $dateFrom);
                $to   = \Carbon\Carbon::createFromFormat('d/m/Y', $dateTo);

                // If same day: use whereDate =
                if ($from->isSameDay($to)) {
                    $query->whereDate('created_at', $from->toDateString());
                } else {
                    $query->whereBetween('created_at', [
                        $from->startOfDay(),
                        $to->endOfDay(),
                    ]);
                }
            } catch (\Exception $e) {
                // ignore parse errors
            }
        }
        $artikuli = $this->artikuliForCurrentUser();
        // Fetch all aptiekas for dropdowns

        $sort = $request->input('sort', 'created_at'); // default sort field
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy('cito', 'desc');

        if ($sort === 'created_at') {
            $hasDatums = Schema::hasColumn('pieprasijumi', 'datums');
            $hasPazinojumaDatums = Schema::hasColumn('pieprasijumi', 'pazinojuma_datums');
            $driver = DB::connection()->getDriverName();

            $dateSortParts = [];
            if ($hasDatums) {
                $dateSortParts[] = 'datums';
            }
            if ($hasPazinojumaDatums && $driver === 'mysql') {
                $dateSortParts[] = "STR_TO_DATE(pazinojuma_datums, '%d/%m/%Y')";
            }
            $dateSortParts[] = 'DATE(created_at)';

            $dateSortExpression = 'COALESCE('.implode(', ', $dateSortParts).')';

            $query->orderByRaw($dateSortExpression.' '.$direction)
                ->orderBy('created_at', $direction)
                ->orderBy('id', $direction);
        } else {
            $query->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc'); // fallback
        }

        $aptiekas = Pharmacy::all();
        $pieprasijumi = $query->paginate(50)->appends($request->query());

        if ($request->ajax()) {
            return view('partials.pieprasijumi-table', [
                'pieprasijumi' => $pieprasijumi,
                'artikuli'     => $artikuli,
            ])->render();
        }

        return view('pieprasijumi.index', compact('pieprasijumi', 'aptiekas', 'artikuli', 'status_filter'))
        ->with('sort', $sort)
        ->with('direction', $direction);
    }

    public function create()
    {
        $aptiekas = Pharmacy::all();
        $artikuli = $this->artikuliForCurrentUser();

        return view('pieprasijumi.create', compact('aptiekas', 'artikuli'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'aptiekas_id' => 'required|exists:aptiekas,id',
            'artikula_id' => 'required|exists:artikuli,id',
            'daudzums' => 'required|integer',
            'izrakstitais_daudzums' => 'nullable|integer',
            'pazinojuma_datums' => 'nullable|string',
            'statuss' => 'nullable|in:Pasūtīts,Atcelts,Mainīta piegāde,Ir noliktavā,Daļēji atlikumā',
            'aizliegums' => 'nullable|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
            'iepircejs' => 'nullable|in:Artūrs,Liene,Anna,Iveta,Kristaps',
            'piegades_datums' => 'nullable|string',
            'piezimes' => 'nullable|string',
            'cito' => 'nullable|boolean',
        ]);

        if (strtolower(auth()->user()->role ?? '') === 'kruzes') {
            $isHiddenFromKruzes = Product::whereKey($validated['artikula_id'])
                ->where('hide_from_kruzes', true)
                ->exists();
            if ($isHiddenFromKruzes) {
                return redirect()->back()->withErrors(['artikula_id' => 'Šis artikuls nav pieejams jūsu lietotājam.'])->withInput();
            }
        }

        Requests::create($validated);

        return redirect()->back()->with('success', 'Pieprasījums veiksmīgi pievienots');
    }

    public function show($id)
    {
        $requestItem = Requests::findOrFail($id);
        return view('pieprasijumi.show', compact('requestItem'));
    }

    public function edit($id)
    {
        $requestItem = Requests::findOrFail($id);
        $aptiekas = Pharmacy::all();
        $artikuli = $this->artikuliForCurrentUser();
        return view('pieprasijumi.edit', compact('requestItem', 'aptiekas', 'artikuli'));
    }

    public function update(Request $request, $id)
    {
        $requestItem = Requests::findOrFail($id);
        
        $validated = $request->validate([
            'aptiekas_id' => 'required|exists:aptiekas,id',
            'artikula_id' => 'required|exists:artikuli,id',
            'daudzums' => 'required|integer',
            'izrakstitais_daudzums' => 'nullable|integer',
            'pazinojuma_datums' => 'nullable|string', // Add format if it's a date
            'statuss' => 'nullable|in:Pasūtīts,Atcelts,Mainīta piegāde,Ir noliktavā,Daļēji atlikumā',
            'aizliegums' => 'nullable|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
            'iepircejs' => 'nullable|in:Artūrs,Liene,Anna,Iveta,Kristaps',
            'piegades_datums' => 'nullable|string', // Add format if it's a date
            'piezimes' => 'nullable|string',
            // Add 'completed' to validation, it's boolean, not part of date issue
            'completed' => 'nullable|boolean', 
            'cito' => 'nullable|boolean',
        ]);

        $validated['cito'] = $request->has('cito');
        if (strtolower(auth()->user()->role ?? '') === 'kruzes') {
            $isHiddenFromKruzes = Product::whereKey($validated['artikula_id'])
                ->where('hide_from_kruzes', true)
                ->exists();
            if ($isHiddenFromKruzes) {
                return redirect()->back()->withErrors(['artikula_id' => 'Šis artikuls nav pieejams jūsu lietotājam.'])->withInput();
            }
        }
        // Logic for completion (keep this as is)
        $isCompleted = $request->has('completed') && $request->completed == '1';
        $oldArtikulaId = $requestItem->artikula_id;
        $newArtikulaId = $validated['artikula_id'];
        
        if ($request->has('completed')) {
            if ($request->completed == '1') {
                $requestItem->completed      = true;
                $requestItem->completed_at   = now();
                $requestItem->who_completed  = auth()->id();
            } else { // completed == '0'
                $requestItem->completed      = false;
                $requestItem->completed_at   = null;
                $requestItem->who_completed  = null;
            }
        }
        
        DB::transaction(function () use ($requestItem, $validated, $oldArtikulaId, $newArtikulaId) {
            if ($oldArtikulaId != $newArtikulaId) {
                $history = $requestItem->previous_artikuli_ids ?? [];
                if (!in_array($oldArtikulaId, $history)) {
                    $history[] = $oldArtikulaId;
                }
                $requestItem->previous_artikuli_ids = array_values(array_unique($history));
            }

            $requestItem->fill($validated);
            $requestItem->save();

            if ($oldArtikulaId != $newArtikulaId) {
                $linkedPieprasijumaId = $this->extractPasutijumaPieprasijumaId($requestItem->piezimes);
                if ($linkedPieprasijumaId) {
                    $this->syncLinkedPasutijumiArtikuls((int) $linkedPieprasijumaId, (int) $oldArtikulaId, (int) $newArtikulaId);
                }
            }
        });

        return redirect()->back()->with('success', 'Pieprasījums veiksmīgi atjaunināts');
    }

    public function destroy($id)
    {
        $requestItem = Requests::findOrFail($id);
        DeleteHistory::create([
            'request_id'           => $requestItem->id,
            'aptiekas_id'          => $requestItem->aptiekas_id,
            'artikula_id'          => $requestItem->artikula_id,
            'daudzums'             => $requestItem->daudzums,
            'izrakstitais_daudzums'=> $requestItem->izrakstitais_daudzums,
            'statuss'              => $requestItem->statuss,
            'aizliegums'           => $requestItem->aizliegums,
            'iepircejs'            => $requestItem->iepircejs,
            'piegades_datums'      => $requestItem->piegades_datums,
            'piezimes'             => $requestItem->piezimes,
            'completed'            => $requestItem->completed,
            'deleted_at'           => Carbon::now(),
        ]);

        $requestItem->delete();

        return redirect()->back()->with('success', 'Pieprasījums dzēsts');
    }
    public function bulkComplete(Request $request)
    {
        $ids = $request->query('ids', []); // or $request->input('ids', []); also works for GET

        if (!is_array($ids) || empty($ids)) {
            return redirect()->back()->with('success', 'Nav izvēlēti ieraksti.');
        }

        Requests::whereIn('id', $ids)
            ->where('completed', false)
            ->update([
                'completed'    => true,
                'completed_at' => now(),
                'who_completed'=> auth()->id(),
            ]);

        return redirect()->back()->with('success', 'Izvēlētie pieprasījumi izpildīti.');
    }

}
