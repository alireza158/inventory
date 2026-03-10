<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CommissionPeriod;
use App\Models\CommissionResult;
use App\Models\User;
use App\Services\CommissionCalculatorService;
use Illuminate\Http\Request;

class CommissionReportController extends Controller
{
    public function __construct(private readonly CommissionCalculatorService $calculator)
    {
    }

    public function calculate(CommissionPeriod $period)
    {
        $summary = $this->calculator->calculateForPeriod($period->id);

        return redirect()
            ->route('commissions.reports.index', ['period_id' => $period->id])
            ->with('success', 'محاسبه انجام شد. مجموع پورسانت: ' . number_format($summary['total_commission']));
    }

    public function index(Request $request)
    {
        $periods = CommissionPeriod::query()->orderByDesc('id')->get();

        $query = CommissionResult::query()->with(['period', 'user', 'category']);

        $periodId = $request->integer('period_id');
        $userId = $request->integer('user_id');
        $categoryId = $request->integer('category_id');

        if ($periodId) {
            $query->where('commission_period_id', $periodId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $results = $query->latest('id')->paginate(50)->withQueryString();

        $users = User::query()->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        $dashboard = [
            'total_sold_amount' => (clone $query)->sum('sold_amount'),
            'total_commission_amount' => (clone $query)->sum('commission_amount'),
            'best_user' => (clone $query)->selectRaw('user_id, SUM(commission_amount) as total')->groupBy('user_id')->orderByDesc('total')->with('user:id,name')->first(),
            'weakest_category' => (clone $query)->selectRaw('category_id, AVG(achievement_percent) as avg_achievement')->groupBy('category_id')->orderBy('avg_achievement')->with('category:id,name')->first(),
            'overall_achievement_percent' => round((float) (clone $query)->avg('achievement_percent'), 2),
        ];

        return view('commissions.reports.index', compact('results', 'periods', 'users', 'categories', 'dashboard', 'periodId', 'userId', 'categoryId'));
    }
}
