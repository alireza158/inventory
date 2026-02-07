<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // آستانه موجودی کم (می‌تونی از config هم بخونی)
        $lowStockThreshold = (int) (config('inventory.low_stock_threshold', 5));

        $totalProducts = Product::count();

        $outOfStock = Product::where('stock', 0)->count();

        // چون low_stock_threshold نداریم، با عدد ثابت حساب می‌کنیم
        $lowStock = Product::where('stock', '>', 0)
            ->where('stock', '<=', $lowStockThreshold)
            ->count();

        // ارزش موجودی: جمع(stock * price) بر اساس خلاصه‌ی product
        // (اگر price خلاصه‌ی کمترین قیمت فروش variants است، این هم بر همان اساس می‌شود)
        $totalStockValue = Product::select(DB::raw('COALESCE(SUM(stock * price),0) as total'))
            ->value('total');

        $latestMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard.index', compact(
            'totalProducts',
            'outOfStock',
            'lowStock',
            'lowStockThreshold',
            'totalStockValue',
            'latestMovements'
        ));
    }
}
