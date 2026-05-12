<?php

namespace App\Services;

use App\Models\InventoryWebhookLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AriyajanebiSyncService
{
    private const LOGIN_URL = 'https://api.ariyajanebi.ir/v1/admin/login';
    private const UPDATE_URL = 'https://api.ariyajanebi.ir/v1/admin/multi_varieties_update_lite';
    private const USERNAME = 'Z.adeli60';
    private const PASSWORD = 'Z.adeli60';

    public static function syncProduct(Product $product): void
    {
        $variants = $product->variants()->whereNotNull('variety_id')->get(['product_id', 'variety_id', 'sell_price', 'stock']);
        if ($variants->isEmpty()) return;
        self::syncVariants($variants, false);
    }

    public static function syncVariant(ProductVariant $variant): void
    {
        if (empty($variant->variety_id)) return;
        self::syncVariants(collect([$variant]), false);
    }

    private static function syncVariants($variants, bool $withoutVerify): void
    {
        $payload = ['_method' => 'PUT'];
        foreach ($variants->values() as $i => $variant) {
            $payload["varieties[{$i}][id]"] = (string) $variant->variety_id;
            $payload["varieties[{$i}][price]"] = (string) max(0, (int) $variant->sell_price);
            $payload["varieties[{$i}][balance]"] = (string) self::centralWarehouseQuantityForVariant($variant);
        }

        $apiLog = self::createApiLog($payload);

        try {
            $client = Http::asForm()->timeout(15)->withOptions(['allow_redirects' => false]);
            if ($withoutVerify) $client = $client->withoutVerifying();

            $login = $client->post(self::LOGIN_URL, [
                'username' => self::USERNAME,
                'password' => self::PASSWORD,
            ]);

            if (!$login->successful()) {
                self::markApiLog($apiLog, 'pending', $login->status(), 'login failed');
                return;
            }

            $updateClient = $client->withCookies($login->cookies()->toArray(), 'api.ariyajanebi.ir');
            $token = self::extractToken($login->json());
            if ($token) {
                $updateClient = $updateClient->withToken($token)->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-API-TOKEN' => $token,
                ]);
            }

            $update = $updateClient->post(self::UPDATE_URL, $payload);

            if ($update->status() >= 300 && $update->status() < 400) {
                self::markApiLog($apiLog, 'pending', $update->status(), 'redirect to ' . ($update->header('Location') ?? 'unknown'));
                return;
            }

            if ($update->successful()) {
                self::markApiLog($apiLog, 'success', $update->status(), null);
            } else {
                self::markApiLog($apiLog, 'pending', $update->status(), mb_substr((string) $update->body(), 0, 500));
            }
        } catch (\Throwable $e) {
            if (!$withoutVerify && str_contains($e->getMessage(), 'cURL error 77')) {
                Log::warning('Ariyajanebi SSL cert issue detected, retrying without SSL verification.');
                self::syncVariants($variants, true);
                return;
            }

            self::markApiLog($apiLog, 'pending', null, mb_substr($e->getMessage(), 0, 500));
            Log::error('Ariyajanebi sync exception', ['message' => $e->getMessage(), 'without_verify' => $withoutVerify]);
        }
    }

    private static function createApiLog(array $payload): ?InventoryWebhookLog
    {
        if (!Schema::hasTable('inventory_webhook_logs')) return null;

        return InventoryWebhookLog::create([
            'event' => 'ariya.multi_varieties_update_lite',
            'target' => self::UPDATE_URL,
            'status' => 'pending',
            'attempts' => 1,
            'payload' => ['payload' => ['variants' => self::variantPayloadPreview($payload)]],
            'sent_at' => now(),
            'next_retry_at' => now()->addMinute(),
        ]);
    }

    private static function variantPayloadPreview(array $payload): array
    {
        $rows = [];
        for ($i = 0; $i < 500; $i++) {
            if (!isset($payload["varieties[{$i}][id]"])) break;
            $rows[] = [
                'id' => $payload["varieties[{$i}][id]"] ?? null,
                'price' => $payload["varieties[{$i}][price]"] ?? null,
                'balance' => $payload["varieties[{$i}][balance]"] ?? null,
            ];
        }
        return $rows;
    }

    private static function markApiLog(?InventoryWebhookLog $log, string $status, ?int $code, ?string $error): void
    {
        if (!$log) return;

        $log->update([
            'status' => $status,
            'response_code' => $code,
            'error_message' => $error,
            'sent_at' => now(),
            'next_retry_at' => $status === 'success' ? null : now()->addMinute(),
        ]);
    }

    private static function centralWarehouseQuantityForVariant($variant): int
    {
        $productId = (int) ($variant->product_id ?? 0);
        if ($productId <= 0) return max(0, (int) ($variant->stock ?? 0));

        $centralWarehouseId = Warehouse::query()->where('type', 'central')->value('id');
        if (!$centralWarehouseId) return max(0, (int) ($variant->stock ?? 0));

        $qty = WarehouseStock::query()
            ->where('warehouse_id', (int) $centralWarehouseId)
            ->where('product_id', $productId)
            ->value('quantity');

        return max(0, (int) ($qty ?? 0));
    }

    private static function extractToken($json): ?string
    {
        if (!is_array($json)) return null;

        $candidates = [
            $json['token'] ?? null,
            $json['access_token'] ?? null,
            $json['data']['token'] ?? null,
            $json['data']['access_token'] ?? null,
            $json['result']['token'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') return $value;
        }

        return null;
    }
}
