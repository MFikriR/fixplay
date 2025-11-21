<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;   // ganti jika model berbeda
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;


class POSController extends Controller{
    public function index(Request $request)
    {
        // ambil produk untuk pilihan POS
        $products = Product::orderBy('name')->get();

        // ambil 10 item penjualan produk terakhir (non-PS). 
        // Menggunakan query builder agar tidak tergantung relasi Eloquent yang mungkin belum ada.
        $recentSales = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereNotNull('sale_items.product_id')
            ->select(
                'sales.id as sale_id',
                'sales.sold_at',
                'products.name as product_name',
                'sale_items.qty',
                'sale_items.unit_price',
                'sale_items.subtotal'
            )
            ->orderBy('sales.sold_at', 'desc')
            ->limit(10)
            ->get();

        return view('pos', [
            'products' => $products,
            'recentSales' => $recentSales,
        ]);
    }

    public function checkout(Request $request)
    {
        // minimal validation: arrays must be present and have same length
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
        $descs      = $request->input('description', []); // optional, your form currently doesn't send description[] but kept for flexibility
        $payMethod  = $request->input('payment_method');
        $paidAmount = (float) $request->input('paid_amount', 0);
        $note       = $request->input('note', null);

        $now = Carbon::now();

        DB::beginTransaction();
        try {
            // Build items array and compute total
            $items = [];
            $total = 0;
            $n = max(count($productIds), count($qtys));
            for ($i=0; $i<$n; $i++) {
                $pid = isset($productIds[$i]) && $productIds[$i] !== '' ? (int)$productIds[$i] : null;
                $qty = isset($qtys[$i]) ? (int)$qtys[$i] : 0;
                if (!$pid && $qty <= 0) {
                    // skip empty row
                    continue;
                }

                // Lookup price from DB (official source) to avoid trusting client
                $unitPrice = 0;
                $productObj = null;
                if ($pid) {
                    $productObj = Product::find($pid);
                    if (!$productObj) {
                        throw new \Exception("Produk dengan id {$pid} tidak ditemukan.");
                    }
                    $unitPrice = (float) $productObj->price;
                } else {
                    // fallback: if POS supports free-text item, you may add support here
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
                    'product_obj' => $productObj, // keep for later stock update
                ];
            }

            if (count($items) === 0) {
                throw new \Exception("Tidak ada item yang valid untuk dibayar.");
            }

            // create sale
            $saleId = DB::table('sales')->insertGetId([
                'sold_at' => $now,
                'total' => $total,
                'payment_method' => $payMethod,
                'paid_amount' => $paidAmount,
                'note' => $note,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // For each item: check stock (if product_id), insert sale_item, decrement stock
            foreach ($items as $it) {
                $pid = $it['product_id'];
                $qty = $it['qty'];
                $unitPrice = $it['unit_price'];
                $desc = $it['description'];
                $subtotal = $it['subtotal'];

                if ($pid) {
                    // lock the product row for update to avoid race conditions
                    $product = Product::where('id', $pid)->lockForUpdate()->first();

                    if (!$product) {
                        throw new \Exception("Produk tidak ditemukan (id={$pid})");
                    }
                    if ($product->stock < $qty) {
                        throw new \Exception("Stok produk \"{$product->name}\" tidak cukup. (stok: {$product->stock}, minta: {$qty})");
                    }

                    // decrement stok (atomic)
                    $product->decrement('stock', $qty);
                    // optionally: $product->save();
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

            // build sale object for receipt view
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

            // langsung tunjukkan receipt (atau redirect ke route lain seperti sales.show)
            return view('sales.receipt', compact('sale'));

        } catch (\Throwable $e) {
            DB::rollBack();
            \log::error('POS checkout error: '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
