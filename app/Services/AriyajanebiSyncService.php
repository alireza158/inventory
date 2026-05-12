<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        try {
            $client = Http::asForm()->timeout(15)->withOptions(['allow_redirects' => false]);
            if ($withoutVerify) {
                $client = $client->withoutVerifying();
            }

            $login = $client->post(self::LOGIN_URL, [
                'username' => self::USERNAME,
                'password' => self::PASSWORD,
            ]);

            if (!$login->successful()) {
                Log::warning('Ariyajanebi login failed', [
                    'status' => $login->status(),
                    'body' => $login->body(),
                    'without_verify' => $withoutVerify,
                ]);
                return;
            }

            $payload = ['_method' => 'PUT'];
            foreach ($variants->values() as $i => $variant) {
                $centralStock = self::centralWarehouseQuantityForVariant($variant);

                $payload["varieties[{$i}][id]"] = (string) $variant->variety_id;
                $payload["varieties[{$i}][price]"] = (string) max(0, (int) $variant->sell_price);
                $payload["varieties[{$i}][balance]"] = (string) $centralStock;
            }

            $cookies = $login->cookies()->toArray();
            $token = self::extractToken($login->json());

            $updateClient = $client->withCookies($cookies, 'api.ariyajanebi.ir');
            if ($token) {
                $updateClient = $updateClient->withToken($token)->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'X-API-TOKEN' => $token,
                ]);
            }

            $update = $updateClient->post(self::UPDATE_URL, $payload);

            if ($update->status() >= 300 && $update->status() < 400) {
                Log::warning('Ariyajanebi update redirected (likely auth/session issue)', [
                    'status' => $update->status(),
                    'location' => $update->header('Location'),
                    'without_verify' => $withoutVerify,
                ]);
                return;
            }

            if (!$update->successful()) {
                Log::warning('Ariyajanebi update failed', [
                    'status' => $update->status(),
                    'body' => $update->body(),
                    'payload' => $payload,
                    'without_verify' => $withoutVerify,
                ]);
            }
        } catch (\Throwable $e) {
            if (!$withoutVerify && str_contains($e->getMessage(), 'cURL error 77')) {
                Log::warning('Ariyajanebi SSL cert issue detected, retrying without SSL verification.');
                self::syncVariants($variants, true);
                return;
            }

            Log::error('Ariyajanebi sync exception', [
                'message' => $e->getMessage(),
                'without_verify' => $withoutVerify,
            ]);
        }
    }



    private static function centralWarehouseQuantityForVariant($variant): int
    {
        $productId = (int) ($variant->product_id ?? 0);
        if ($productId <= 0) {
            return max(0, (int) ($variant->stock ?? 0));
        }

        $centralWarehouseId = Warehouse::query()->where('type', 'central')->value('id');
        if (!$centralWarehouseId) {
            return max(0, (int) ($variant->stock ?? 0));
        }

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
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
