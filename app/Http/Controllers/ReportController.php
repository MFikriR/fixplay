<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Product;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        // --- parse filters ---
        $range = $r->query('range', 'day'); // day|week|month|custom

        $refDate = $r->query('date') ? Carbon::parse($r->query('date')) : Carbon::now();

        if ($range === 'day') {
            $start = $refDate->copy()->startOfDay();
            $end   = $refDate->copy()->endOfDay();
        } elseif ($range === 'week') {
            $start = $refDate->copy()->startOfWeek();
            $end   = $refDate->copy()->endOfWeek();
        } elseif ($range === 'month') {
            $start = $refDate->copy()->startOfMonth();
            $end   = $refDate->copy()->endOfMonth();
        } else { // custom
            $s = $r->query('start');
            $e = $r->query('end');
            $start = $s ? Carbon::parse($s)->startOfDay() : Carbon::now()->startOfDay();
            $end   = $e ? Carbon::parse($e)->endOfDay() : Carbon::now()->endOfDay();
        }

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        // --- TOTALS (Card Ringkasan) ---
        $ps_total = DB::table('sale_items')
            ->join('sales','sale_items.sale_id','sales.id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->whereNull('sale_items.product_id')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal),0) as total')
            ->value('total');

        $prod_total = DB::table('sale_items')
            ->join('sales','sale_items.sale_id','sales.id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->whereNotNull('sale_items.product_id')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal),0) as total')
            ->value('total');

        $sales_total = ($ps_total ?? 0) + ($prod_total ?? 0);

        $expenses_total = DB::table('expenses')
            ->whereBetween('timestamp', [$start, $end])
            ->selectRaw('COALESCE(SUM(amount),0) as total')
            ->value('total');

        // --- PERBAIKAN 1: LIST PENJUALAN (Agar muncul nama produk POS) ---
        $sales = Sale::with(['items.product']) // Load items beserta produknya
            ->whereBetween('sold_at', [$start, $end])
            ->orderBy('sold_at','desc')
            ->limit(100)
            ->get()
            ->map(function($s){
                // 1. Fix Total (jika header 0, hitung dari items)
                $totalFix = $s->total ?? $s->total_amount ?? 0;
                if ($totalFix == 0) {
                    $totalFix = $s->items->sum('subtotal');
                }
                $s->total = $totalFix;

                // 2. Fix Judul/Catatan (Ambil nama produk jika ada)
                $names = [];
                foreach($s->items as $it) {
                    if($it->product) {
                        // Format: "Mie (2)", "Rosta (1)"
                        $names[] = $it->product->name . ' (' . $it->qty . ')';
                    }
                }
                
                // Jika ada produk, gabungkan namanya. Jika tidak, pakai note asli.
                $displayTitle = !empty($names) ? implode(', ', $names) : $s->note;
                
                // Simpan ke properti baru untuk ditampilkan di view
                $s->display_note = $displayTitle ?: 'Item'; 

                return $s;
            });

        // --- Expenses list ---
        $expenses = DB::table('expenses')
            ->whereBetween('timestamp', [$start, $end])
            ->orderBy('timestamp','desc')
            ->limit(100)
            ->get()
            ->map(function($e){
                $e->timestamp = isset($e->timestamp) ? Carbon::parse($e->timestamp) : null;
                $e->timestamp_fmt = $e->timestamp ? $e->timestamp->format('d-m H:i') : '';
                return $e;
            });

        // --- PERBAIKAN 2: REKAP PER PERIODE (Pisahkan PS dan Produk dengan SQL) ---
        
        // 1. Daily Rows
        $daily_rows = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw("
                DATE(sales.sold_at) as d, 
                SUM(sale_items.subtotal) as total,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NULL THEN sale_items.subtotal ELSE 0 END),0) as ps_amount,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NOT NULL THEN sale_items.subtotal ELSE 0 END),0) as prod_amount
            ")
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('d')
            ->orderBy('d','asc')
            ->get()
            ->map(function($row){
                return (object)[
                    'label' => Carbon::parse($row->d)->format('d-m-Y'),
                    'ps'    => (int)$row->ps_amount,
                    'prod'  => (int)$row->prod_amount,
                    'total' => (int)$row->total
                ];
            });

        // 2. Weekly Rows
        $weekly_rows = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw("
                YEAR(sales.sold_at) as y, WEEK(sales.sold_at,1) as w, 
                SUM(sale_items.subtotal) as total,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NULL THEN sale_items.subtotal ELSE 0 END),0) as ps_amount,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NOT NULL THEN sale_items.subtotal ELSE 0 END),0) as prod_amount
            ")
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('y','w')
            ->orderBy('y','asc')->orderBy('w','asc')
            ->get()
            ->map(function($r){
                return (object)[
                    'label' => "W{$r->w}-{$r->y}",
                    'ps'    => (int)$r->ps_amount,
                    'prod'  => (int)$r->prod_amount,
                    'total' => (int)$r->total
                ];
            });

        // 3. Monthly Rows
        $monthly_rows = DB::table('sales')
            ->join('sale_items', 'sales.id', '=', 'sale_items.sale_id')
            ->selectRaw("
                DATE_FORMAT(sales.sold_at,'%Y-%m') as m, 
                SUM(sale_items.subtotal) as total,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NULL THEN sale_items.subtotal ELSE 0 END),0) as ps_amount,
                COALESCE(SUM(CASE WHEN sale_items.product_id IS NOT NULL THEN sale_items.subtotal ELSE 0 END),0) as prod_amount
            ")
            ->whereBetween('sales.sold_at', [$start, $end])
            ->groupBy('m')
            ->orderBy('m','asc')
            ->get()
            ->map(function($r){
                return (object)[
                    'label' => Carbon::parse($r->m . '-01')->format('M Y'),
                    'ps'    => (int)$r->ps_amount,
                    'prod'  => (int)$r->prod_amount,
                    'total' => (int)$r->total
                ];
            });

        // --- Top products ---
        $top = DB::table('sale_items')
            ->join('sales','sale_items.sale_id','sales.id')
            ->join('products','sale_items.product_id','products.id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('products.name as name, SUM(sale_items.qty) as qty, SUM(sale_items.subtotal) as amount')
            ->groupBy('products.id','products.name')
            ->orderByRaw('SUM(sale_items.qty) desc')
            ->limit(10)
            ->get();

        $low_stock = Product::where('stock','<=',5)->orderBy('stock','asc')->get();

        return view('reports.index', [
            'start_date'     => $start,
            'end_date'       => $end,
            'start_date_str' => $start->format('Y-m-d'),
            'end_date_str'   => $end->format('Y-m-d'),
            'ps_total'       => (int)$ps_total,
            'prod_total'     => (int)$prod_total,
            'sales_total'    => (int)$sales_total,
            'expenses_total' => (int)$expenses_total,
            'sales'          => $sales,
            'expenses'       => $expenses,
            'daily_rows'     => $daily_rows,
            'weekly_rows'    => $weekly_rows,
            'monthly_rows'   => $monthly_rows,
            'top'            => $top,
            'low_stock'      => $low_stock,
            'range'          => $range,
            'ref_date'       => $refDate,
            'd'              => Carbon::now(),
        ]);
    }
}