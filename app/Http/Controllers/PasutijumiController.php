<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasutijums;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class PasutijumiController extends Controller
{
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

    protected function nextPasutijumaNumurs(): string
    {
        $max = Pasutijums::query()
            ->whereNotNull('pasutijuma_numurs')
            ->pluck('pasutijuma_numurs')
            ->filter(fn ($v) => is_string($v) && preg_match('/^\d{1,7}$/', trim($v)))
            ->map(fn ($v) => (int) trim($v))
            ->max();

        $next = ($max ?? 0) + 1;

        return str_pad((string) $next, 7, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        $sort      = $request->query('sort', 'datums');
        $direction = $request->query('direction', 'desc');

        // only allow safe columns/directions
        if (!in_array($sort, ['datums'])) {
            $sort = 'datums';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query = Pasutijums::with(['product', 'creator', 'completer', 'canceller'])->orderBy($sort, $direction);

        // Hidden-from-everyone orders are visible only to the special user.
        if (!$isSpecialUser) {
            $query->where('hide_from_visiem', false);
        }

        // Extra filter shown only to the special user.
        if ($isSpecialUser && $request->query('mine_filter') === 'mine') {
            $query->where('created_by', auth()->id());
        }

        // search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q2) use ($search) {
                    $q2->where('nosaukums', 'like', "%{$search}%");
                })
                ->orWhere('farmaceita_nosaukums', 'like', "%{$search}%")
                ->orWhere('pasutijuma_numurs', 'like', "%{$search}%")
                ->orWhere('receptes_numurs', 'like', "%{$search}%")
                ->orWhere('vards_uzvards', 'like', "%{$search}%")
                ->orWhere('talrunis_epasts', 'like', "%{$search}%");
            });
        }

        // status_filter: neizpildits | done | all
        $statusFilter = $request->query('status_filter', 'all');

        if ($statusFilter === 'neizpildits') {
            $query->where('statuss', 'neizpildits');
        } elseif ($statusFilter === 'done') {
            // Izpildītie & Atceltie
            $query->whereIn('statuss', ['izpildits', 'atcelts']);
        } elseif ($statusFilter === 'all') {
            // no filter
        } else {
            $query->where('statuss', 'all');
        }

        // date range filter (dd/mm/YYYY expected)
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        if ($dateFrom && $dateTo) {
            try {
                $from = Carbon::createFromFormat('d/m/Y', $dateFrom)->startOfDay();
                $to   = Carbon::createFromFormat('d/m/Y', $dateTo)->endOfDay();
                $query->whereBetween('datums', [$from, $to]);
            } catch (\Exception $e) {
                // ignore parse errors
            }
        }

        $pasutijumi = $query->paginate(50)->appends($request->query());

        $artikuliQuery = Product::query()->orderBy('nosaukums');
        if (strtolower(auth()->user()->role ?? '') === 'farmaceiti') {
            $artikuliQuery->where('hide_from_farmaceiti', false);
        }
        $artikuli = $artikuliQuery->get();

        if ($request->ajax()) {
            return view('partials.pasutijumi-table', compact('pasutijumi','artikuli'))->render();
        }

        return view('pasutijumi.index', compact('pasutijumi','artikuli','sort','direction'));
    }

    public function store(Request $request)
    {
        if (strtolower(auth()->user()->role ?? '') === 'farmaceiti') {
            abort(403);
        }

        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        $input = $request->all();
        if (isset($input['skaits'])) {
            $input['skaits'] = str_replace(',', '.', $input['skaits']);
        }
        $request->replace($input);
        $data = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
            'artikula_id' => 'required|exists:artikuli,id',
            'skaits' => 'required|numeric|min:0.01',
            'vards_uzvards' => 'required|string|max:255',
            'pasutijuma_numurs' => 'nullable|string|max:191',
            'receptes_numurs' => 'nullable|string|max:191',
            'talrunis_epasts' => 'nullable|string|max:255',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'pasutijuma_datums' => 'nullable|date_format:d/m/Y',
            'komentari' => 'nullable|string',
            'statuss' => 'nullable|in:izpildits,neizpildits,atcelts,neapstradats',
            'hide_from_visiem' => 'boolean',
        ]);

        if (!empty($data['datums'])) {
            $data['datums'] = Carbon::createFromFormat('d/m/Y', $data['datums'])->toDateString();
        } else {
            unset($data['datums']);
        }

        if (!empty($data['pasutijuma_datums'])) {
            $data['pasutijuma_datums'] = Carbon::createFromFormat('d/m/Y', $data['pasutijuma_datums'])->toDateString();
        } else {
            unset($data['pasutijuma_datums']);
        }

        if (empty($data['statuss'])) {
            $data['statuss'] = 'neizpildits';
        }

        if ($this->shouldHideDoctorFieldsForArtikuls((int) ($data['artikula_id'] ?? 0))) {
            $data['arstniecibas_iestade'] = null;
            $data['arsts'] = null;
        }

        $data['farmaceita_nosaukums'] = null;

        if (Schema::hasColumn('pasutijumi', 'hide_from_visiem')) {
            $data['hide_from_visiem'] = $isSpecialUser ? $request->boolean('hide_from_visiem') : false;
        }

        // default
        $data['created_by'] = auth()->id();
        $data['who_completed'] = null;
        $data['completed_at']  = null;
        $data['who_cancelled'] = null;
        $data['cancelled_at']  = null;

        // new record already created as completed
        if ($data['statuss'] === 'izpildits') {
            $data['who_completed'] = auth()->id();
            $data['completed_at']  = now();
        } elseif ($data['statuss'] === 'atcelts') {
            $data['who_cancelled'] = auth()->id();
            $data['cancelled_at']  = now();
        }

        Pasutijums::create($data);

        $returnUrl = $request->input('return_url', route('pasutijumi.index', ['status_filter' => 'all']));

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Pasūtījums saglabāts.');
    }

    public function storeKvits(Request $request)
    {
        if (strtolower(auth()->user()->role ?? '') !== 'farmaceiti') {
            abort(403);
        }

        $input = $request->all();
        if (isset($input['skaits'])) {
            $input['skaits'] = str_replace(',', '.', $input['skaits']);
        }
        $request->replace($input);

        $request->merge([
            'artikula_id' => $request->filled('artikula_id') ? $request->input('artikula_id') : null,
        ]);

        $data = $request->validate([
            'artikula_id' => 'nullable|exists:artikuli,id',
            'farmaceita_nosaukums' => 'nullable|string|max:512',
            'skaits' => 'required|numeric|min:0.01',
            'vards_uzvards' => 'required|string|max:255',
            'receptes_numurs' => 'nullable|string|max:191',
            'talrunis' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $digits = preg_replace('/\D/u', '', (string) $value);
                    if (strlen($digits) < 8) {
                        $fail('Tālrunī jābūt vismaz 8 cipariem.');
                    }
                },
            ],
            'epasts' => 'nullable|string|max:255',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'komentari' => 'nullable|string',
        ]);

        $artikulaId = ! empty($data['artikula_id']) ? (int) $data['artikula_id'] : null;
        $farmaceita = isset($data['farmaceita_nosaukums']) ? trim($data['farmaceita_nosaukums']) : '';

        if ($artikulaId) {
            $isHidden = Product::whereKey($artikulaId)
                ->where('hide_from_farmaceiti', true)
                ->exists();
            if ($isHidden) {
                return redirect()->back()->withErrors(['artikula_id' => 'Šis artikuls nav pieejams.'])->withInput();
            }
        } elseif ($farmaceita === '') {
            return redirect()->back()->withErrors([
                'farmaceita_nosaukums' => 'Ievadiet zāļu nosaukumu vai izvēlieties artikulu no saraksta.',
            ])->withInput();
        }

        $tel = trim((string) ($data['talrunis'] ?? ''));
        $mail = trim((string) ($data['epasts'] ?? ''));
        $talrunisEpasts = collect([$tel, $mail])->filter()->implode(' / ');

        $today = Carbon::now()->toDateString();
        $todayDmY = Carbon::now()->format('d/m/Y');

        $hideDoctorFields = $this->shouldHideDoctorFieldsForArtikuls($artikulaId);

        $payload = [
            'datums' => null,
            'artikula_id' => $artikulaId,
            'farmaceita_nosaukums' => $artikulaId ? null : $farmaceita,
            'skaits' => $data['skaits'],
            'pasutijuma_numurs' => $this->nextPasutijumaNumurs(),
            'receptes_numurs' => $data['receptes_numurs'] ?? null,
            'vards_uzvards' => $data['vards_uzvards'],
            'talrunis_epasts' => $talrunisEpasts !== '' ? $talrunisEpasts : null,
            'arstniecibas_iestade' => $hideDoctorFields ? null : ($data['arstniecibas_iestade'] ?? null),
            'arsts' => $hideDoctorFields ? null : ($data['arsts'] ?? null),
            'pasutijuma_datums' => $today,
            'komentari' => $data['komentari'] ?? null,
            'statuss' => 'neapstradats',
            'created_by' => auth()->id(),
            'who_completed' => null,
            'completed_at' => null,
            'who_cancelled' => null,
            'cancelled_at' => null,
        ];

        if (Schema::hasColumn('pasutijumi', 'hide_from_visiem')) {
            $payload['hide_from_visiem'] = false;
        }

        Pasutijums::create($payload);

        $returnUrl = $request->input(
            'return_url',
            route('pasutijumi.index', ['status_filter' => 'all'])
        );

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Kvīts saglabāta (statuss: neapstrādāts, Pasūt. nr. '.$payload['pasutijuma_numurs'].', pasūtījuma datums '.$todayDmY.').');
    }

    public function update(Request $request, Pasutijums $pasutijumi)
    {
        if (strtolower(auth()->user()->role ?? '') === 'farmaceiti') {
            abort(403);
        }

        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        if ($isSpecialUser && (int) $pasutijumi->created_by !== (int) auth()->id()) {
            abort(403);
        }
        $input = $request->all();
        if (isset($input['skaits'])) {
            $input['skaits'] = str_replace(',', '.', $input['skaits']);
        }
        $request->merge([
            'artikula_id' => $request->filled('artikula_id') ? $request->input('artikula_id') : null,
        ]);

        $data = $request->validate([
            'datums' => 'nullable|date_format:d/m/Y',
            'artikula_id' => 'nullable|exists:artikuli,id',
            'farmaceita_nosaukums' => 'nullable|string|max:512',
            'skaits' => 'required|numeric|min:0.01',
            'vards_uzvards' => 'required|string|max:255',
            'pasutijuma_numurs' => 'nullable|string|max:191',
            'receptes_numurs' => 'nullable|string|max:191',
            'talrunis_epasts' => 'nullable|string|max:255',
            'arstniecibas_iestade' => 'nullable|string|max:255',
            'arsts' => 'nullable|string|max:255',
            'pasutijuma_datums' => 'nullable|date_format:d/m/Y',
            'komentari' => 'nullable|string',
            'statuss' => 'nullable|in:izpildits,neizpildits,atcelts,neapstradats',
            'hide_from_visiem' => 'boolean',
        ]);

        if (! empty($data['artikula_id'])) {
            $data['farmaceita_nosaukums'] = null;
        } else {
            $farm = isset($data['farmaceita_nosaukums']) ? trim($data['farmaceita_nosaukums']) : '';
            if ($farm === '') {
                return redirect()->back()->withErrors([
                    'farmaceita_nosaukums' => 'Norādiet zāļu nosaukumu vai izvēlieties artikulu no saraksta.',
                ])->withInput();
            }
            $data['farmaceita_nosaukums'] = $farm;
            $data['artikula_id'] = null;
        }

        if ($this->shouldHideDoctorFieldsForArtikuls((int) ($data['artikula_id'] ?? 0))) {
            $data['arstniecibas_iestade'] = null;
            $data['arsts'] = null;
        }
        if (!empty($data['datums'])) {
            $data['datums'] = Carbon::createFromFormat('d/m/Y', $data['datums'])->toDateString();
        } else {
            $data['datums'] = null;
        }

        if (!empty($data['pasutijuma_datums'])) {
            $data['pasutijuma_datums'] = Carbon::createFromFormat('d/m/Y', $data['pasutijuma_datums'])->toDateString();
        } else {
            $data['pasutijuma_datums'] = null;
        }

        if (empty($data['statuss'])) {
            $data['statuss'] = 'neizpildits';
        }

        if (Schema::hasColumn('pasutijumi', 'hide_from_visiem')) {
            $data['hide_from_visiem'] = $isSpecialUser ? $request->boolean('hide_from_visiem') : false;
        }

        $wasCompleted = (bool) $pasutijumi->completed;
        $wasCancelled = ($pasutijumi->statuss === 'atcelts');

        $data['who_completed'] = $pasutijumi->who_completed;
        $data['completed_at']  = $pasutijumi->completed_at;
        $data['who_cancelled'] = $pasutijumi->who_cancelled;
        $data['cancelled_at']  = $pasutijumi->cancelled_at;

        // if status changed to izpildits and it was not completed before
        if ($data['statuss'] === 'izpildits' && !$wasCompleted) {
            $data['who_completed'] = auth()->id();
            $data['completed_at']  = now();
        }

        // if status is not izpildits anymore, reset completed info
        if ($data['statuss'] !== 'izpildits') {
            $data['who_completed'] = null;
            $data['completed_at']  = null;
        }

        // if status changed to atcelts and it was not cancelled before
        if ($data['statuss'] === 'atcelts' && !$wasCancelled) {
            $data['who_cancelled'] = auth()->id();
            $data['cancelled_at']  = now();
        }

        // if status is not atcelts anymore, reset cancelled info
        if ($data['statuss'] !== 'atcelts') {
            $data['who_cancelled'] = null;
            $data['cancelled_at']  = null;
        }

        $pasutijumi->update($data);

        $returnUrl = $request->input('return_url', route('pasutijumi.index', ['status_filter' => 'all']));

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Pasūtījums atjaunināts.');
    }


    public function destroy(Request $request, Pasutijums $pasutijumi)
    {
        if (strtolower(auth()->user()->role ?? '') === 'farmaceiti') {
            abort(403);
        }

        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        if ($isSpecialUser && (int) $pasutijumi->created_by !== (int) auth()->id()) {
            abort(403);
        }

        $pasutijumi->delete();

        $returnUrl = $request->input('return_url');

        if ($returnUrl) {
            return redirect()->to($returnUrl)->with('success', 'Pasūtījums izdzēsts.');
        }

        // Fallback – default filter "all"
        return redirect()
            ->route('pasutijumi.index', ['status_filter' => 'all'])
            ->with('success', 'Pasūtījums izdzēsts.');
    }
}
