<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CrmProductSyncService
{
    public function sync(): array
    {
        $base = rtrim(config('services.ariya_crm.base_url'), '/');
        $url  = $base . '/products';

        $req = Http::timeout(30)->retry(3, 500);

        $token = config('services.ariya_crm.token');
        if (!empty($token)) {
            $req = $req->withToken($token);
        }

        $created = 0;
        $updated = 0;
        $failed  = 0;

        $nextUrl = $url;

        while ($nextUrl) {
            $payload = $req->get($nextUrl)->throw()->json();

            $items = Arr::get($payload, 'data.items.data', []);
            if (!is_array($items)) $items = [];

            foreach ($items as $item) {
                try {
                    DB::transaction(function () use ($item, &$created, &$updated) {

                        $externalId = (string) Arr::get($item, 'ariya_id');
                        $name       = (string) Arr::get($item, 'title');
                        $basePrice  = (int) (Arr::get($item, 'base_price') ?? 0);
                        $baseQty    = (int) (Arr::get($item, 'base_quantity') ?? 0);

                        $varieties  = Arr::get($item, 'varieties', []);
                        if (!is_array($varieties)) $varieties = [];

                        $sku = 'ARIYA-' . $externalId;
                        $categoryId = 1;

                        $existing = Product::where('sku', $sku)->first();
                        $isExisting = (bool) $existing;

                        $product = Product::updateOrCreate(
                            ['sku' => $sku],
                            [
                                'external_id' => $externalId ?: null,
                                'category_id' => $categoryId,
                                'name'        => $name,
                                'synced_at'   => Carbon::now(),
                                // اینا رو بعداً از مدل‌ها/پایه آپدیت می‌کنیم
                                'price'       => 0,
                                'stock'       => 0,
                            ]
                        );

                        if (count($varieties)) {
                            $now = Carbon::now();

                            $rowsByVarietyId = [];
                            $rowsByUniqueKey = [];

                            foreach ($varieties as $v) {
                                // ✅ فیلد درست طبق payload شما
                                $varietyId = Arr::get($v, 'variety_id'); // نه id
                                $uniqueKey = (string) (Arr::get($v, 'unique_key') ?? '');
                                $modelName = (string) (Arr::get($v, 'model_name') ?? Arr::get($v, 'title') ?? 'unknown');
                                $sellPrice = (int) (Arr::get($v, 'price') ?? 0);
                                $qty       = (int) (Arr::get($v, 'quantity') ?? 0);

                                $row = [
                                    'product_id'   => $product->id,
                                    'variant_name' => $modelName,
                                    'variety_id'   => $varietyId ?: null,
                                    'unique_key'   => $uniqueKey ?: null,
                                    'sell_price'   => max(0, $sellPrice),
                                    'buy_price'    => null,
                                    'stock'        => max(0, $qty),
                                    'reserved'     => 0,
                                    'synced_at'    => $now,
                                    'created_at'   => $now,
                                    'updated_at'   => $now,
                                ];

                                if (!empty($varietyId)) {
                                    $rowsByVarietyId[] = $row;
                                } else {
                                    // اگر variety_id نداریم، باید با unique_key یکتا کنیم
                                    if ($uniqueKey !== '') {
                                        $rowsByUniqueKey[] = $row;
                                    }
                                }
                            }

                            if (count($rowsByVarietyId)) {
                                ProductVariant::upsert(
                                    $rowsByVarietyId,
                                    ['product_id', 'variety_id'],
                                    ['variant_name','unique_key','sell_price','buy_price','stock','reserved','synced_at','updated_at']
                                );
                            }

                            if (count($rowsByUniqueKey)) {
                                ProductVariant::upsert(
                                    $rowsByUniqueKey,
                                    ['product_id', 'unique_key'],
                                    ['variant_name','variety_id','sell_price','buy_price','stock','reserved','synced_at','updated_at']
                                );
                            }

                            // جمع‌بندی محصول
                            $stock = (int) ProductVariant::where('product_id', $product->id)->sum('stock');
                            $minPrice = ProductVariant::where('product_id', $product->id)->min('sell_price');

                            $product->update([
                                'stock' => max(0, $stock),
                                'price' => max(0, (int)($minPrice ?? $basePrice)),
                            ]);

                        } else {
                            // محصول بدون مدل
                            $product->update([
                                'stock' => max(0, $baseQty),
                                'price' => max(0, $basePrice),
                            ]);
                        }

                        if ($isExisting) $updated++; else $created++;
                    });

                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('CRM product sync failed', [
                        'external_id' => Arr::get($item, 'ariya_id'),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // ✅ pagination
            $nextUrl = Arr::get($payload, 'data.items.next_page_url');
        }

        return compact('created', 'updated', 'failed');
    }
}
