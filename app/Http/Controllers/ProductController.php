<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = $r->query('q');
        $query = Product::orderBy('name');

        if ($q) {
            $query->where(function($sub){
                $sub->where('name', 'like', '%'.request('q').'%')
                    ->orWhere('category', 'like', '%'.request('q').'%');
            });
        }

        $items = $query->paginate(20);
        return view('products.index', compact('items'));
    }

    public function create()
    {
        return view('products.create'); // jika ingin halaman create terpisah
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'price'    => 'required|integer|min:0',
            'stock'    => 'required|integer|min:0',
            'unit'     => 'nullable|string|max:30',
        ]);

        Product::create($data);
        return redirect()->route('products.index')->with('success','Produk disimpan.');
    }

    public function update(Request $r, $id)
    {
        $data = $r->validate([
            'name'     => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'price'    => 'required|integer|min:0',
            'stock'    => 'required|integer|min:0',
            'unit'     => 'nullable|string|max:30',
        ]);

        $p = Product::findOrFail($id);
        $p->update($data);
        return redirect()->route('products.index')->with('success','Produk diperbarui.');
    }

    public function destroy($id)
    {
        $p = Product::findOrFail($id);
        $p->delete();
        return redirect()->route('products.index')->with('success','Produk dihapus.');
    }
}
