<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Sale;      // <--- PERBAIKAN: Import Model Sale
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class POSController extends Controller
{
    public function index(Request $request)
    {
        // Ambil produk untuk pilihan di form POS
        $products = Product::orderBy('name')->get();

        // Ambil Riwayat Transaksi (Header), bukan per Item.
        // Menggunakan 'Sale::with' agar bisa mengambil detail itemnya sekaligus.
        $recentSales = Sale::with(['items.product'])
            ->whereHas('items', function($q){
                // Hanya ambil transaksi yang punya produk fisik (bukan murni rental PS)
                $q->whereNotNull('product_id'); 
            })
            ->orderBy('sold_at', 'desc')
            ->limit(10)
            ->get();

        return view('pos', [
            'products' => $products,
            'recentSales' => $recentSales,
        ]);
    }

    public function checkout(Request $request)
    {
        // Validasi input
        $request->validate([
            'product_id' => 'required|array',
            'product_id.*' => 'nullable|integer|exists:products,id',
            'qty'        => 'required|array',
            'qty.*'      => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'paid_amount'    => 'required|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);

        $productIds = $request->input('product_id', []);
        $qtys       = $request->input('qty', []);
        $descs      = $request->input('description', []); 
        $payMethod  = $request->input('payment_method');
        $paidAmount = (float) $request->input('paid_amount', 0);
        $note       = $request->input('note', null);

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            // Hitung total dan siapkan array item
            $items = [];
            $total = 0;
            $n = max(count($productIds), count($qtys));

            for ($i=0; $i<$n; $i++) {
                $pid = isset($productIds[$i]) && $productIds[$i] !== '' ? (int)$productIds[$i] : null;
                $qty = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
                
                // Skip baris kosong
                if (!$pid && $qty <= 0) {
                    continue;
                }

                // Ambil harga dari database
                $unitPrice = 0;
                $productObj = null;
                if ($pid) {
                    $productObj = Product::find($pid);
                    if (!$productObj) {
                        throw new \Exception("Produk dengan id {$pid} tidak ditemukan.");
                    }
                    $unitPrice = (float) $productObj->price;
                } else {
                    $unitPrice = 0;
                }

                $subtotal = $unitPrice * $qty;
                $total += $subtotal;

                $items[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'description' => $descs[$i] ?? null,
                    'subtotal' => $subtotal,
                    'product_obj' => $productObj, 
                ];
            }

            if (count($items) === 0) {
                throw new \Exception("Tidak ada item yang valid untuk dibayar.");
            }

            // Simpan Header Penjualan (Sales)
            $saleId = DB::table('sales')->insertGetId([
                'sold_at' => $now,
                'total' => $total, // Simpan total di header
                'payment_method' => $payMethod,
                'paid_amount' => $paidAmount,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Simpan Detail Item (Sale Items) & Kurangi Stok
            foreach ($items as $it) {
                $pid = $it['product_id'];
                $qty = $it['qty'];
                $unitPrice = $it['unit_price'];
                $desc = $it['description'];
                $subtotal = $it['subtotal'];

                if ($pid) {
                    // Lock produk untuk update stok aman
                    $product = Product::where('id', $pid)->lockForUpdate()->first();

                    if (!$product) {
                        throw new \Exception("Produk tidak ditemukan (id={$pid})");
                    }
                    if ($product->stock < $qty) {
                        throw new \Exception("Stok produk \"{$product->name}\" tidak cukup. (stok: {$product->stock}, minta: {$qty})");
                    }

                    $product->decrement('stock', $qty);
                }

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $pid,
                    'description' => $desc,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::commit();

            // Buat objek sale untuk view struk
            $sale = (object)[
                'id' => $saleId,
                'timestamp' => $now,
                'total' => $total,
                'payment_method' => $payMethod,
                'paid_amount' => $paidAmount,
                'change_amount' => max(0, $paidAmount - $total),
                'note' => $note,
                'items' => array_map(function($it){
                    return (object)[
                        'product' => $it['product_id'] ? Product::find($it['product_id']) : null,
                        'description' => $it['description'],
                        'qty' => $it['qty'],
                        'unit_price' => $it['unit_price'],
                        'subtotal' => $it['subtotal'],
                    ];
                }, $items),
            ];

            // PERBAIKAN: Kirim parameter 'backUrl' agar tombol kembali mengarah ke POS
            return view('sales.receipt', [
                'sale' => $sale,
                'backUrl' => route('pos.index') 
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('POS checkout error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}