<?php

namespace App\Http\Controllers\Vaccination;

use App\Vaccine;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VaccineController extends Controller
{
    public function index()
    {
        $vaccines = Vaccine::all();
        return view('vaccination.vaccines.index', compact('vaccines'));
    }

    public function create()
    {
        return view('vaccination.vaccines.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:vaccines',
            'name' => 'required',
            'manufacturer' => 'required',
            'recommended_age' => 'required',
            'dose_interval' => 'integer|nullable',
            'storage_requirements' => 'string|nullable'
        ]);

        Vaccine::create($request->all());
        return redirect()->route('vaccines.index')->with('success', 'Vaccine added successfully.');
    }

    public function show(Vaccine $vaccine)
    {
        return view('vaccination.vaccines.show', compact('vaccine'));
    }

    public function edit(Vaccine $vaccine)
    {
        return view('vaccination.vaccines.edit', compact('vaccine'));
    }

    public function update(Request $request, Vaccine $vaccine)
    {
        $request->validate([
            'code' => 'required',
            'name' => 'required',
            'manufacturer' => 'required',
            'recommended_age' => 'required',
            'dose_interval' => 'integer|nullable',
            'storage_requirements' => 'string|nullable'
        ]);

        $vaccine->update($request->all());
        return redirect()->route('vaccines.index')->with('success', 'Vaccine updated successfully.');
    }

    public function destroy(Vaccine $vaccine)
    {
        $vaccine->delete();
        return redirect()->route('vaccines.index')->with('success', 'Vaccine deleted successfully.');
    }
}
