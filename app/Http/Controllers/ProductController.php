<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product; // Make sure your model exists

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nosaukums', 'like', "%{$search}%")
                ->orWhere('id_numurs', 'like', "%{$search}%")
                ->orWhere('valsts', 'like', "%{$search}%");
            });
        }
        $products = $query->orderBy('nosaukums')->paginate(50)->appends($request->query());

        if ($request->ajax()) {
            return view('partials.artikuli-table', compact('products'))->render();
        }

        return view('artikuli.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('artikuli.create'); // create.blade.php
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nosaukums' => 'required|string|max:255',
            'id_numurs' => 'nullable|string|max:255',
            'valsts' => 'nullable|string|max:255',
            'snn' => 'nullable|string',
            'analogs' => 'nullable|string|max:255',
            'atzimes' => 'nullable|string',
        ]);

        Product::create($validated);

        return redirect()->back()->with('success', 'Artikuls veiksmīgi pievienots');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return view('artikuli.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('artikuli.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nosaukums' => 'required|string|max:255',
            'id_numurs' => 'nullable|string|max:255',
            'valsts' => 'nullable|string|max:255',
            'snn' => 'nullable|string',
            'analogs' => 'nullable|string|max:255',
            'atzimes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($id);
        $product->update($validated);

        return redirect()->back()->with('success', 'Artikuls veiksmīgi atjaunināts');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Artikuls dzēsts');
    }
}
