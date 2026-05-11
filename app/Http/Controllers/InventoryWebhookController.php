<?php

namespace App\Http\Controllers;

use App\Models\InventoryWebhookLog;
use App\Models\InventoryWebhookSetting;
use Illuminate\Http\Request;

class InventoryWebhookController extends Controller
{
    public function index()
    {
        $setting = InventoryWebhookSetting::query()->latest('id')->first();
        $logs = InventoryWebhookLog::query()->latest('id')->limit(100)->get();

        return view('inventory-webhooks.index', compact('setting', 'logs'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'is_enabled' => 'nullable|boolean',
            'endpoint_url' => 'nullable|url|max:2048',
            'secret' => 'nullable|string|max:255',
            'timeout_seconds' => 'required|integer|min:1|max:30',
        ]);

        $setting = InventoryWebhookSetting::query()->latest('id')->first() ?? new InventoryWebhookSetting();
        $setting->fill([
            'is_enabled' => (bool)($data['is_enabled'] ?? false),
            'endpoint_url' => $data['endpoint_url'] ?? null,
            'secret' => $data['secret'] ?? null,
            'timeout_seconds' => $data['timeout_seconds'],
        ])->save();

        return redirect()->route('inventory-webhooks.index')->with('success', 'تنظیمات API با موفقیت ذخیره شد.');
    }
}
