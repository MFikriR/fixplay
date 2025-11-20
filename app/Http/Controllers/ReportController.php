<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        // --- parse filters ---
        $range = $r->query('range', 'day'); // day|week|month|custom

        // refDate should be Carbon instance
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

        // Safety: ensure Carbon instances
        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        // --- totals ---
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

        // --- sales list (recent within range) ---
        $sales = DB::table('sales')
            ->whereBetween('sold_at', [$start, $end])
            ->orderBy('sold_at','desc')
            ->limit(100)
            ->get()
            ->map(function($s){
                // Make sold_at a Carbon instance and add formatted string
                $s->sold_at = isset($s->sold_at) ? Carbon::parse($s->sold_at) : null;
                $s->sold_at_fmt = $s->sold_at ? $s->sold_at->format('d-m H:i') : '';
                return $s;
            });

        // --- expenses list within range ---
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

        // --- rekap per period (daily / weekly / monthly) ---
        $daily_rows = DB::table('sales')
            ->selectRaw('DATE(sold_at) as d, SUM(total) as total')
            ->whereBetween('sold_at', [$start, $end])
            ->groupBy('d')
            ->orderBy('d','asc')
            ->get()
            ->map(function($row){
                $label = Carbon::parse($row->d)->format('d-m-Y');
                return (object)[
                    'label' => $label,
                    'ps'    => 0,
                    'prod'  => (int)$row->total,
                    'total' => (int)$row->total
                ];
            });

        $weekly_rows = DB::table('sales')
            ->selectRaw("YEAR(sold_at) as y, WEEK(sold_at,1) as w, SUM(total) as total")
            ->whereBetween('sold_at', [$start, $end])
            ->groupBy('y','w')
            ->orderBy('y','asc')->orderBy('w','asc')
            ->get()
            ->map(function($r){
                return (object)[
                    'label' => "W{$r->w}-{$r->y}",
                    'ps'    => 0,
                    'prod'  => (int)$r->total,
                    'total' => (int)$r->total
                ];
            });

        $monthly_rows = DB::table('sales')
            ->selectRaw("DATE_FORMAT(sold_at,'%Y-%m') as m, SUM(total) as total")
            ->whereBetween('sold_at', [$start, $end])
            ->groupBy('m')
            ->orderBy('m','asc')
            ->get()
            ->map(function($r){
                return (object)[
                    'label' => Carbon::parse($r->m . '-01')->format('M Y'),
                    'ps'    => 0,
                    'prod'  => (int)$r->total,
                    'total' => (int)$r->total
                ];
            });

        // --- top products by qty and omzet in range ---
        $top = DB::table('sale_items')
            ->join('sales','sale_items.sale_id','sales.id')
            ->join('products','sale_items.product_id','products.id')
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('products.name as name, SUM(sale_items.qty) as qty, SUM(sale_items.subtotal) as amount')
            ->groupBy('products.id','products.name')
            ->orderByRaw('SUM(sale_items.qty) desc')
            ->limit(10)
            ->get();

        // --- low stock products (<=5) ---
        $low_stock = Product::where('stock','<=',5)->orderBy('stock','asc')->get();

        // Additional vars used by Blade template
        $ref_date = $refDate;        // Carbon
        $d = Carbon::now();

        // Also pass formatted strings in case view expects strings
        $start_date_str = $start->format('Y-m-d');
        $end_date_str   = $end->format('Y-m-d');

        // pass to view (start_date and end_date are Carbon instances)
        return view('reports.index', [
            'start_date'     => $start,
            'end_date'       => $end,
            'start_date_str' => $start_date_str,
            'end_date_str'   => $end_date_str,
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
            'ref_date'       => $ref_date,
            'd'              => $d,
        ]);
    }
}
