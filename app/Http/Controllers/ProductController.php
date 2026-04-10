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
        $role = strtolower(auth()->user()->role ?? '');
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        $effectiveRole = $isSpecialUser ? 'farmaceiti' : $role;

        if ($effectiveRole === 'kruzes') {
            $query->where('hide_from_kruzes', false);
        } elseif ($effectiveRole === 'farmaceiti') {
            $query->where('hide_from_farmaceiti', false);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nosaukums', 'like', "%{$search}%")
                ->orWhere('id_numurs', 'like', "%{$search}%")
                ->orWhere('valsts', 'like', "%{$search}%");
            });
        }
        if ($request->filled('snn')) {
            $snn = $request->input('snn');
            $query->where('snn', 'like', "%{$snn}%");
        }

        // Role-based ordering:
        // - farmaceiti: sort by SNN
        // - others: sort by nosaukums (current default)
        if ($effectiveRole === 'farmaceiti') {
            $products = $query->orderBy('snn')->paginate(50)->appends($request->query());
        } else {
            $products = $query->orderBy('nosaukums')->paginate(50)->appends($request->query());
        }

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

    protected function role()
    {
        return strtolower(auth()->user()->role ?? '');
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $role = $this->role();
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';

        if ($isSpecialUser) {
            abort(403);
        }

        $rules = [
            'nosaukums' => 'required|string|max:255',
            'id_numurs' => 'nullable|string|max:255',
            'valsts' => 'nullable|string|max:255',
            'snn' => 'nullable|string',
            'analogs' => 'nullable|string|max:255',
            'atzimes' => 'nullable|string',
        ];

        if ($role === 'brivibas') {
            $rules = array_merge($rules, [
                    'atk' => 'nullable|string|max:50',
                    'atk_validity_days' => 'nullable|in:90,365',
                    'info' => 'nullable|string',
                    'pielietojums' => 'nullable|string',
                    'hide_from_kruzes' => 'boolean',
                    'hide_from_farmaceiti' => 'boolean',
                    'without_arst' => 'boolean',
                    'nemedikamenti' => 'boolean',
                ]);
            }
        
        $data = $request->validate($rules);
        // normalize checkboxes
        $data['hide_from_kruzes'] = $request->boolean('hide_from_kruzes');
        $data['hide_from_farmaceiti'] = $request->boolean('hide_from_farmaceiti');
        $data['without_arst'] = $request->boolean('without_arst');
        $data['nemedikamenti'] = $request->boolean('nemedikamenti');
        // Default validity to 1 gads when not provided.
        if ($role === 'brivibas') {
            $data['atk_validity_days'] = $data['atk_validity_days'] ?? 90;
        }
        
        if ($role === 'kruzes') {
            // ignore hidden/extra fields
            unset($data['atk'], $data['info'], $data['pielietojums'], $data['hide_from_kruzes'], $data['hide_from_farmaceiti'], $data['without_arst'], $data['nemedikamenti']);
        } elseif ($role === 'farmaceiti') {
            // farmaceiti should not create/edit at all
            abort(403);
        }

        Product::create($data);

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
        $role = $this->role();
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';

        if ($isSpecialUser) {
            abort(403);
        }

        $rules = [
            'nosaukums' => 'required|string|max:255',
            'id_numurs' => 'nullable|string|max:255',
            'valsts' => 'nullable|string|max:255',
            'snn' => 'nullable|string',
            'analogs' => 'nullable|string|max:255',
            'atzimes' => 'nullable|string',
        ];

        if ($role === 'brivibas') {
            $rules = array_merge($rules, [
                    'atk' => 'nullable|string|max:50',
                    'atk_validity_days' => 'nullable|in:90,365',
                    'info' => 'nullable|string',
                    'pielietojums' => 'nullable|string',
                    'hide_from_kruzes' => 'boolean',
                    'hide_from_farmaceiti' => 'boolean',
                    'without_arst' => 'boolean',
                    'nemedikamenti' => 'boolean',
                ]);
            }
        
        $data = $request->validate($rules);
        // normalize checkboxes
        $data['hide_from_kruzes'] = $request->boolean('hide_from_kruzes');
        $data['hide_from_farmaceiti'] = $request->boolean('hide_from_farmaceiti');
        $data['without_arst'] = $request->boolean('without_arst');
        $data['nemedikamenti'] = $request->boolean('nemedikamenti');
        if ($role === 'brivibas') {
            $data['atk_validity_days'] = $data['atk_validity_days'] ?? 90;
        }
        
        if ($role === 'kruzes') {
            // ignore hidden/extra fields
            unset($data['atk'], $data['info'], $data['pielietojums'], $data['hide_from_kruzes'], $data['hide_from_farmaceiti'], $data['without_arst'], $data['nemedikamenti']);
        } elseif ($role === 'farmaceiti') {
            // farmaceiti should not create/edit at all
            abort(403);
        }

        $product = Product::findOrFail($id);
        $product->update($data);

        return redirect()->back()->with('success', 'Artikuls veiksmīgi atjaunināts');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $isSpecialUser = strtolower(auth()->user()->email ?? '') === 'd.grazule@saulesaptieka.lv';
        if ($isSpecialUser) {
            abort(403);
        }

        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->back()->with('success', 'Artikuls dzēsts');
    }
}
