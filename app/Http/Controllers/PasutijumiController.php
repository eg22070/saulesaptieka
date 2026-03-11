<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasutijums;
use App\Models\Product;
use Carbon\Carbon;

class PasutijumiController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->query('sort', 'datums');
        $direction = $request->query('direction', 'desc');

        // only allow safe columns/directions
        if (!in_array($sort, ['datums'])) {
            $sort = 'datums';
        }
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $query = Pasutijums::with('product')->orderBy($sort, $direction);

        // search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q2) use ($search) {
                    $q2->where('nosaukums', 'like', "%{$search}%");
                })
                ->orWhere('pasutijuma_numurs', 'like', "%{$search}%")
                ->orWhere('receptes_numurs', 'like', "%{$search}%")
                ->orWhere('vards_uzvards', 'like', "%{$search}%")
                ->orWhere('talrunis_epasts', 'like', "%{$search}%");
            });
        }

        // status filter (expects izpildits|neizpildits|atcelts)
        $statusFilter = $request->query('status_filter', 'neizpildits');

        if ($statusFilter === 'neizpildits') {
            $query->where('statuss', 'neizpildits');
        } elseif ($statusFilter === 'done') {
            // Izpildītie & Atceltie
            $query->whereIn('statuss', ['izpildits', 'atcelts']);
        } elseif ($statusFilter === 'all') {
            // no filter
        } else {
            // fallback default
            $query->where('statuss', 'neizpildits');
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

        $pasutijumi = $query->paginate(25)->appends($request->query());
        $artikuli = Product::orderBy('nosaukums')->get();

        if ($request->ajax()) {
            return view('partials.pasutijumi-table', compact('pasutijumi','artikuli'))->render();
        }

        return view('pasutijumi.index', compact('pasutijumi','artikuli','sort','direction'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
            'artikula_id' => 'required|exists:artikuli,id',
            'skaits' => 'required|integer|min:1',
            'vards_uzvards' => 'required|string|max:255',
            'pasutijuma_numurs' => 'nullable|string|max:191',
            'receptes_numurs' => 'nullable|string|max:191',
            'talrunis_epasts' => 'nullable|string|max:255',
            'pasutijuma_datums' => 'nullable|date_format:d/m/Y',
            'komentari' => 'nullable|string',
            'statuss' => 'nullable|in:izpildits,neizpildits,atcelts',
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

        // default
        $data['created_by'] = auth()->id();
        $data['who_completed'] = null;
        $data['completed_at']  = null;

        // new record already created as completed
        if ($data['statuss'] === 'izpildits') {
            $data['who_completed'] = auth()->id();
            $data['completed_at']  = now();
        }

        Pasutijums::create($data);

        $returnUrl = $request->input('return_url', route('pasutijumi.index', ['status_filter' => 'neizpildits']));

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Pasūtījums saglabāts.');
    }

    public function update(Request $request, Pasutijums $pasutijumi)
    {
        $data = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
            'artikula_id' => 'required|exists:artikuli,id',
            'skaits' => 'required|integer|min:1',
            'vards_uzvards' => 'required|string|max:255',
            'pasutijuma_numurs' => 'nullable|string|max:191',
            'receptes_numurs' => 'nullable|string|max:191',
            'talrunis_epasts' => 'nullable|string|max:255',
            'pasutijuma_datums' => 'nullable|date_format:d/m/Y',
            'komentari' => 'nullable|string',
            'statuss' => 'nullable|in:izpildits,neizpildits,atcelts',
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

        $wasCompleted = (bool) $pasutijumi->completed;

        $data['who_completed'] = $pasutijumi->who_completed;
        $data['completed_at']  = $pasutijumi->completed_at;

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

        $pasutijumi->update($data);

        $returnUrl = $request->input('return_url', route('pasutijumi.index', ['status_filter' => 'neizpildits']));

        return redirect()
            ->to($returnUrl)
            ->with('success', 'Pasūtījums atjaunināts.');
    }


    public function destroy(Request $request, Pasutijums $pasutijumi)
    {
        $pasutijumi->delete();

        $returnUrl = $request->input('return_url');

        if ($returnUrl) {
            return redirect()->to($returnUrl)->with('success', 'Pasūtījums izdzēsts.');
        }

        // Fallback – default filter "neizpildits"
        return redirect()
            ->route('pasutijumi.index', ['status_filter' => 'neizpildits'])
            ->with('success', 'Pasūtījums izdzēsts.');
    }
}
