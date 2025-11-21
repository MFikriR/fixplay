<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;       // model untuk tabel sales
use App\Models\SaleItem;   // model untuk sale_items jika ada
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function show($id)
    {
        // ambil header penjualan
        $sale = Sale::find($id);
        if (!$sale) {
            abort(404);
        }

        // ambil item penjualan (sesuaikan relasi / kolom sesuai struktur DB)
        // jika Anda punya relasi di model Sale: $sale->items, gunakan itu
        $items = DB::table('sale_items')
            ->where('sale_id', $sale->id)
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->select('sale_items.*', 'products.name as product_name')
            ->get();

        // siapkan data view; view ada di resources/views/sales/receipt.blade.php sesuai tree Anda
        return view('sales.receipt', [
            'sale' => $sale,
            'items' => $items
        ]);
    }
}
