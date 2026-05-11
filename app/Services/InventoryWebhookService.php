<?php

namespace App\Services;

use App\Models\InventoryWebhookLog;
use App\Models\InventoryWebhookSetting;
use Illuminate\Support\Facades\Http;

class InventoryWebhookService
{
    public static function send(string $event, array $payload): void
    {
        $setting = InventoryWebhookSetting::query()->latest('id')->first();

        if (!$setting || !$setting->is_enabled || empty($setting->endpoint_url)) {
            return;
        }

        $log = InventoryWebhookLog::create([
            'setting_id' => $setting->id,
            'event' => $event,
            'status' => 'pending',
            'payload' => $payload,
        ]);

        try {
            $body = [
                'event' => $event,
                'sent_at' => now()->toIso8601String(),
                'payload' => $payload,
            ];

            $response = Http::timeout(max(1, (int) $setting->timeout_seconds))
                ->acceptJson()
                ->withHeaders([
                    'X-Inventory-Event' => $event,
                    'X-Inventory-Signature' => $setting->secret ? hash_hmac('sha256', json_encode($body, JSON_UNESCAPED_UNICODE), (string) $setting->secret) : '',
                ])
                ->post($setting->endpoint_url, $body);

            $log->update([
                'status' => $response->successful() ? 'success' : 'failed',
                'response_code' => $response->status(),
                'error_message' => $response->successful() ? null : mb_substr((string) $response->body(), 0, 2000),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 2000),
                'sent_at' => now(),
            ]);
        }
    }
}
