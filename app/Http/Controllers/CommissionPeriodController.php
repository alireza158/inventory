<?php

namespace App\Http\Controllers;

use App\Http\Requests\Commission\StoreCommissionPeriodRequest;
use App\Http\Requests\Commission\UpdateCommissionPeriodRequest;
use App\Models\CommissionPeriod;

class CommissionPeriodController extends Controller
{
    public function index()
    {
        $periods = CommissionPeriod::query()->latest('id')->paginate(20);

        return view('commissions.periods.index', compact('periods'));
    }

    public function store(StoreCommissionPeriodRequest $request)
    {
        CommissionPeriod::query()->create($request->validated() + ['created_by' => auth()->id()]);

        return back()->with('success', 'دوره پورسانت با موفقیت ایجاد شد.');
    }

    public function update(UpdateCommissionPeriodRequest $request, CommissionPeriod $period)
    {
        $period->update($request->validated());

        return back()->with('success', 'دوره پورسانت بروزرسانی شد.');
    }

    public function close(CommissionPeriod $period)
    {
        $period->update(['status' => 'closed']);

        return back()->with('success', 'دوره پورسانت بسته شد.');
    }
}
