<?php

namespace App\Http\Controllers;

use App\Http\Requests\Commission\StoreCommissionTargetRequest;
use App\Http\Requests\Commission\UpdateCommissionTargetRequest;
use App\Models\Category;
use App\Models\CommissionPeriod;
use App\Models\CommissionTarget;
use App\Models\User;

class CommissionTargetController extends Controller
{
    public function index()
    {
        $targets = CommissionTarget::query()
            ->with(['period', 'user', 'category'])
            ->latest('id')
            ->paginate(30);

        $periods = CommissionPeriod::query()->orderByDesc('id')->get();
        $users = User::query()->orderBy('name')->get();
        $categories = Category::query()->orderBy('name')->get();

        return view('commissions.targets.index', compact('targets', 'periods', 'users', 'categories'));
    }

    public function store(StoreCommissionTargetRequest $request)
    {
        CommissionTarget::query()->create($request->validated());

        return back()->with('success', 'تارگت پورسانت ذخیره شد.');
    }

    public function update(UpdateCommissionTargetRequest $request, CommissionTarget $target)
    {
        $target->update($request->validated());

        return back()->with('success', 'تارگت پورسانت بروزرسانی شد.');
    }
}
