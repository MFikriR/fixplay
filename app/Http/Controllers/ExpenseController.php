<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;

class ExpenseController extends Controller
{
    public function index(Request $r)
    {
        $query = Expense::orderBy('timestamp','desc');

        // optional: filter by q
        if ($q = $r->query('q')) {
            $query->where(function($q2) use ($q) {
                $q2->where('category','like','%'.$q.'%')->orWhere('description','like','%'.$q.'%');
            });
        }

        $items = $query->paginate(20);
        return view('purchases.expenses', compact('items'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'category' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|integer|min:0',
            'timestamp' => 'nullable|date',
        ]);

        if (empty($data['timestamp'])) $data['timestamp'] = now();

        Expense::create($data);
        return redirect()->route('purchases.expenses.index')->with('success','Pengeluaran tersimpan.');
    }

    public function update(Request $r, $id)
    {
        $data = $r->validate([
            'category' => 'required|string|max:191',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|integer|min:0',
            'timestamp' => 'nullable|date',
        ]);

        $e = Expense::findOrFail($id);
        $e->update($data);
        return redirect()->route('purchases.expenses.index')->with('success','Pengeluaran diperbarui.');
    }

    public function destroy($id)
    {
        $e = Expense::findOrFail($id);
        $e->delete();
        return redirect()->route('purchases.expenses.index')->with('success','Pengeluaran dihapus.');
    }
}
