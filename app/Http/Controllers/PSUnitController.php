<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PSUnit;

class PSUnitController extends Controller
{
    public function index()
    {
        $units = PSUnit::orderBy('name')->get();
        return view('ps_units', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'hourly_rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        PSUnit::create($data);
        return redirect()->route('ps_units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $unit = PSUnit::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'hourly_rate' => 'required|numeric|min:0',
        ]);
        $unit->update($data);
        return redirect()->route('ps_units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    public function toggle($id)
    {
        $unit = PSUnit::findOrFail($id);
        $unit->is_active = !$unit->is_active;
        $unit->save();
        return redirect()->route('ps_units.index')->with('success', 'Status unit diperbarui.');
    }

    public function destroy($id)
    {
        $unit = PSUnit::findOrFail($id);
        $unit->delete();
        return redirect()->route('ps_units.index')->with('success', 'Unit dihapus.');
    }
}
