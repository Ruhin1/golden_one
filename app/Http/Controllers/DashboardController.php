<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
 
        // ================= আজকের অবস্থা =================
        $todaySalesQuery = Sale::whereBetween('sale_date', [$todayStart, $todayEnd]);
        $todaySales = (clone $todaySalesQuery)->sum('net_amount');
        $todayInstantPaid = (clone $todaySalesQuery)->sum('initial_paid_amount');
        $todayDueCreated = (clone $todaySalesQuery)->sum(DB::raw('net_amount - initial_paid_amount'));
        $todayInvoiceCount = (clone $todaySalesQuery)->count();
        $todayPaymentsCollected = CustomerPayment::whereBetween('payment_date', [$todayStart, $todayEnd])->sum('amount');
        $todayTotalCollected = $todayInstantPaid + $todayPaymentsCollected;
 
        // ================= সামগ্রিক ব্যবসার অবস্থা =================
        $totalDue = (float) Customer::sum('current_due');
        $customersWithDue = Customer::where('current_due', '>', 0)->count();
        $totalCustomers = Customer::count();
 
        $stockValue = Product::where('is_active', true)->get()->sum(function ($p) {
            return ($p->stock_in_grams / 1000) * (float) $p->price_1kg;
        });
 
        // ================= গত ৩০ দিনের ট্রেন্ড (চার্টের জন্য) =================
        $rangeStart = now()->subDays(29)->startOfDay();
        $dailyRaw = Sale::selectRaw('DATE(sale_date) as d, SUM(net_amount) as sales, SUM(paid_amount) as paid, SUM(due_amount) as due')
            ->where('sale_date', '>=', $rangeStart)
            ->groupBy('d')
            ->get()
            ->keyBy('d');
 
        $trend = collect(range(0, 29))->map(function ($i) use ($dailyRaw) {
            $date = now()->subDays(29 - $i)->format('Y-m-d');
            $row = $dailyRaw->get($date);
            return [
                'date'  => \Carbon\Carbon::parse($date)->format('d M'),
                'sales' => $row ? (float) $row->sales : 0,
                'paid'  => $row ? (float) $row->paid : 0,
                'due'   => $row ? (float) $row->due : 0,
            ];
        });
 
        // ================= ক্যাটাগরি অনুযায়ী বিক্রয় (গত ৩০ দিন) =================
        $categorySales = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.sale_date', '>=', $rangeStart)
            ->whereNull('sales.deleted_at')
            ->selectRaw('categories.name as category, SUM(sale_items.subtotal) as total')
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
 
        // ================= কম স্টক পণ্য =================
        $lowStockProducts = Product::where('is_active', true)
            ->where('stock_in_grams', '<=', 2000)
            ->orderBy('stock_in_grams')
            ->limit(10)
            ->get();
 
        // ================= সর্বোচ্চ বকেয়া কাস্টমার =================
        $topDueCustomers = Customer::where('current_due', '>', 0)
            ->orderByDesc('current_due')
            ->limit(8)
            ->get();
 
        // ================= সাম্প্রতিক ইনভয়েস ও পেমেন্ট =================
        $recentSales = Sale::with('customer')->latest('sale_date')->limit(8)->get();
        $recentPayments = CustomerPayment::with('customer')->latest('payment_date')->limit(8)->get();
 
        // ================= টপ সেলিং পণ্য (গত ৩০ দিন) =================
        $topProducts = DB::table('sale_items')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.sale_date', '>=', $rangeStart)
            ->whereNull('sales.deleted_at')
            ->selectRaw('products.name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit(8)
            ->get();
 
        // ================= স্টাফ পারফরম্যান্স (গত ৩০ দিন) =================
        $staffPerformance = Sale::with('user')
            ->where('sale_date', '>=', $rangeStart)
            ->selectRaw('user_id, COUNT(*) as invoice_count, SUM(net_amount) as total_sales, SUM(paid_amount) as total_paid')
            ->groupBy('user_id')
            ->orderByDesc('total_sales')
            ->get();
 
        return view('admin.dashboard.index', compact(
            'todaySales', 'todayTotalCollected', 'todayDueCreated', 'todayInvoiceCount',
            'totalDue', 'customersWithDue', 'totalCustomers', 'stockValue',
            'trend', 'categorySales', 'lowStockProducts', 'topDueCustomers',
            'recentSales', 'recentPayments', 'topProducts', 'staffPerformance'
        ));
    }

}
