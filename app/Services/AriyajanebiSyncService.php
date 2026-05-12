<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AriyajanebiSyncService
{
    private const LOGIN_URL = 'https://api.ariyajanebi.ir/v1/admin/login';
    private const UPDATE_URL = 'https://api.ariyajanebi.ir/v1/admin/multi_varieties_update_lite';
    private const USERNAME = 'admin';
    private const PASSWORD = 'Z.adeli60';

    public static function syncProduct(Product $product): void
    {
        $variants = $product->variants()
            ->whereNotNull('variety_id')
            ->get(['variety_id', 'sell_price', 'stock']);

        if ($variants->isEmpty()) {
            return;
        }

        self::syncVariants($variants);
    }

    public static function syncVariant(ProductVariant $variant): void
    {
        if (empty($variant->variety_id)) {
            return;
        }

        self::syncVariants(collect([$variant]));
    }

    private static function syncVariants($variants): void
    {
        try {
            $login = Http::asForm()->post(self::LOGIN_URL, [
                'username' => self::USERNAME,
                'password' => self::PASSWORD,
            ]);

            if (!$login->successful()) {
                Log::warning('Ariyajanebi login failed', ['status' => $login->status(), 'body' => $login->body()]);
                return;
            }

            $cookies = $login->cookies();
            $payload = ['_method' => 'PUT'];

            foreach ($variants->values() as $i => $variant) {
                $payload["varieties[{$i}][id]"] = (string) $variant->variety_id;
                $payload["varieties[{$i}][price]"] = (string) max(0, (int) $variant->sell_price);
                $payload["varieties[{$i}][balance]"] = (string) max(0, (int) $variant->stock);
            }

            $update = Http::asForm()->withCookies($cookies->toArray(), 'api.ariyajanebi.ir')->post(self::UPDATE_URL, $payload);

            if (!$update->successful()) {
                Log::warning('Ariyajanebi update failed', ['status' => $update->status(), 'body' => $update->body(), 'payload' => $payload]);
            }
        } catch (\Throwable $e) {
            Log::error('Ariyajanebi sync exception', ['message' => $e->getMessage()]);
        }
    }
}
