<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(Request $request)
    {
        $query = Pharmacy::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nosaukums', 'like', '%' . $search . '%')
                ->orWhere('adrese', 'like', '%' . $search . '%');
            });
        }

        // Sort by name in alphabetical order
        $pharmacies = $query->orderBy('nosaukums')->paginate(50)->appends($request->query());

        if ($request->ajax()) {
            return view('partials.pharmacies-table', compact('pharmacies'))->render();
        }
        return view('pharmacies.index', compact('pharmacies'));
    }

    public function create()
    {
        return view('pharmacies.create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nosaukums' => 'required|max:255',
            'adrese' => 'required|max:255',
        ]);

        Pharmacy::create($validatedData);

        return redirect()->back()->with('success', 'Aptieka pievienota!');
    }

    public function show(Pharmacy $pharmacy)
    {
        return view('pharmacies.show', compact('pharmacy'));
    }

    public function edit(Pharmacy $pharmacy)
    {
        return view('pharmacies.edit', compact('pharmacy'));
    }

    public function update(Request $request, Pharmacy $pharmacy)
    {
        $validatedData = $request->validate([
            'nosaukums' => 'required|max:255',
            'adrese' => 'required|max:255',
        ]);

        $pharmacy->update($validatedData);

        return redirect()->back()->with('success', 'Aptieka atjaunota!');
    }

    public function destroy(Pharmacy $pharmacy)
    {
        $pharmacy->delete();

        return redirect()->back()->with('success', 'Aptieka izdzēsta!');
    }
}
