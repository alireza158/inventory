<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $lowStockThreshold = (int) (config('inventory.low_stock_threshold', 5));

        $totalProducts = Product::count();
        $outOfStock = Product::where('stock', 0)->count();
        $lowStock = Product::where('stock', '>', 0)
            ->where('stock', '<=', $lowStockThreshold)
            ->count();

        $inStock = max(0, $totalProducts - $outOfStock);
        $lowStockRate = $totalProducts > 0
            ? round(($lowStock / $totalProducts) * 100)
            : 0;

        $totalStockValue = Product::select(DB::raw('COALESCE(SUM(stock * price),0) as total'))
            ->value('total');

        $topLowStockProducts = Product::query()
            ->where('stock', '>', 0)
            ->where('stock', '<=', $lowStockThreshold)
            ->orderBy('stock')
            ->orderBy('name')
            ->take(6)
            ->get(['id', 'name', 'sku', 'stock']);

        $latestMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $todayMovements = StockMovement::query()
            ->whereDate('created_at', now()->toDateString())
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as total_in")
            ->selectRaw("SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as total_out")
            ->first();

        return view('dashboard.index', compact(
            'totalProducts',
            'outOfStock',
            'inStock',
            'lowStock',
            'lowStockRate',
            'lowStockThreshold',
            'totalStockValue',
            'latestMovements',
            'topLowStockProducts',
            'todayMovements',
        ));
    }
}
