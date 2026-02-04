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
        $status_filter = $request->input('status_filter'); // Add this line
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
        if ($status = $request->input('status_filter')) {
            if ($status === 'completed') {
                $query->where('completed', true);
            } elseif ($status === 'incomplete') {
                $query->where('completed', false);
            }
            // if 'all', then no filter applied
        }
        $artikuli = Product::all();
        // Fetch all aptiekas for dropdowns
        $aptiekas = Pharmacy::all();
        $pieprasijumi = $query->orderBy('datums', 'asc')->paginate(10);

        if ($request->ajax()) {
            return view('partials.pieprasijumi-table', compact('pieprasijumi'))->render();
        }

        return view('pieprasijumi.index', compact('pieprasijumi', 'aptiekas', 'artikuli', 'status_filter'));
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

        return redirect()->route('pieprasijumi.index')->with('success', 'Pieprasījums veiksmīgi pievienots');
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


        // Logic for completion (keep this as is)
        $isCompleted = $request->has('completed') && $request->completed == '1';
        
        if ($request->has('completed') && $request->completed == '1') {
            $validated['completed'] = true;
            $validated['completed_at'] = now();
            $validated['who_completed'] = auth()->id();
        }else {
            $validated['completed'] = false;
            $validated['completed_at'] = null;
            $validated['who_completed'] = null;
        }
        
        $requestItem->update($validated);
        
        return redirect()->route('pieprasijumi.index')->with('success', 'Pieprasījums veiksmīgi atjaunināts');
    }

    public function destroy($id)
    {
        $requestItem = Requests::findOrFail($id);
        $requestItem->delete();

        return redirect()->route('pieprasijumi.index')->with('success', 'Pieprasījums dzēsts');
    }
}
