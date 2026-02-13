<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requests;
use App\Models\Pharmacy;  // Assuming such model exists
use App\Models\Product;
use Carbon\Carbon;

class RequestController extends Controller
{
    public function index(Request $request)
    {
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
        $artikuli = Product::all();
        // Fetch all aptiekas for dropdowns

        $sort      = $request->input('sort', 'datums');      // default sort field
        $direction = $request->input('direction', 'asc');   // default ascending

        if ($sort === 'datums') {
            $query->orderBy('datums', $direction);
        } else {
            $query->orderBy('datums', 'asc'); // fallback
        }

        $aptiekas = Pharmacy::all();
        $pieprasijumi = $query->paginate(50)->appends($request->query());

        if ($request->ajax()) {
            return view('partials.pieprasijumi-table', compact('pieprasijumi'))->render();
        }

        return view('pieprasijumi.index', compact('pieprasijumi', 'aptiekas', 'artikuli', 'status_filter'))
        ->with('sort', $sort)
        ->with('direction', $direction);
    }

    public function create()
    {
        $aptiekas = Pharmacy::all();
        $artikuli = Product::all();

        return view('pieprasijumi.create', compact('aptiekas', 'artikuli'));
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'datums' => 'required|date_format:d/m/Y',
            'aptiekas_id' => 'required|exists:aptiekas,id',
            'artikula_id' => 'required|exists:artikuli,id',
            'daudzums' => 'required|integer',
            'izrakstitais_daudzums' => 'nullable|integer',
            'pazinojuma_datums' => 'nullable|string',
            'statuss' => 'nullable|in:Pasūtīts,Atcelts,Mainīta piegāde,Ir noliktavā,Daļēji atlikumā',
            'aizliegums' => 'nullable|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
            'iepircejs' => 'nullable|in:Artūrs,Liene,Anna,Iveta',
            'piegades_datums' => 'nullable|string',
            'piezimes' => 'nullable|string',
        ]);
        $validated['datums'] = Carbon::createFromFormat('d/m/Y', $validated['datums'])->format('Y-m-d');

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
        $artikuli = Product::all();
        return view('pieprasijumi.edit', compact('requestItem', 'aptiekas', 'artikuli'));
    }

    public function update(Request $request, $id)
    {
        $requestItem = Requests::findOrFail($id);
        
        $validated = $request->validate([
            'datums' => 'required|date_format:d/m/Y', // Validation is correct
            'aptiekas_id' => 'required|exists:aptiekas,id',
            'artikula_id' => 'required|exists:artikuli,id',
            'daudzums' => 'required|integer',
            'izrakstitais_daudzums' => 'nullable|integer',
            'pazinojuma_datums' => 'nullable|string', // Add format if it's a date
            'statuss' => 'nullable|in:Pasūtīts,Atcelts,Mainīta piegāde,Ir noliktavā,Daļēji atlikumā',
            'aizliegums' => 'nullable|in:Drīkst aizvietot,Nedrīkst aizvietot,NVD,Stacionārs',
            'iepircejs' => 'nullable|in:Artūrs,Liene,Anna,Iveta',
            'piegades_datums' => 'nullable|string', // Add format if it's a date
            'piezimes' => 'nullable|string',
            // Add 'completed' to validation, it's boolean, not part of date issue
            'completed' => 'nullable|boolean', 
        ]);

        // --- NEW CONVERSION STEP ---
        // Convert the 'datums' string from 'd/m/Y' to a Carbon object
        $validated['datums'] = Carbon::createFromFormat('d/m/Y', $validated['datums'])->format('Y-m-d');

        $requestItem->update($validated);
        // Logic for completion (keep this as is)
        $isCompleted = $request->has('completed') && $request->completed == '1';
        
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
            $requestItem->save();
        }
        
        
        
        return redirect()->back()->with('success', 'Pieprasījums veiksmīgi atjaunināts');
    }

    public function destroy($id)
    {
        $requestItem = Requests::findOrFail($id);
        $requestItem->delete();

        return redirect()->back()->with('success', 'Pieprasījums dzēsts');
    }
}
