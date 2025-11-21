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
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->whereHas('aptiekas', function($q2) use ($search) {
                    $q2->where('nosaukums', 'like', "%$search%");
                })
                ->orWhereHas('artikuli', function($q3) use ($search) {
                    $q3->where('valsts', 'like', "%$search%")
                    ->orWhere('id_numurs', 'like', "%$search%")
                    ->orWhere('nosaukums', 'like', "%$search%");
                })
                ->orWhere('iepircejs', 'like', "%$search%");
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

        $pieprasijumi->transform(function ($request) {
            $request->datums = Carbon::createFromFormat('Y-m-d', $request->datums)->format('d/m/Y'); // Adjust this line if needed
            return $request;
        });

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
            'datums' => 'required|date',
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
        $validated = $request->validate([
            'datums' => 'required|date',
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
        $validated['completed'] = $request->has('completed') ? true : false;
        $requestItem = Requests::findOrFail($id);
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
