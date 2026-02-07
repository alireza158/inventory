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
        if (!empty($token)) $req = $req->withToken($token);

        $payload = $req->get($url)->throw()->json();

        $items = Arr::get($payload, 'data.items.data', []);
        if (!is_array($items)) $items = [];

        $created = 0;
        $updated = 0;
        $failed  = 0;

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

                    $product = Product::where('sku', $sku)->first();
                    $isExisting = (bool) $product;

                    $product = Product::updateOrCreate(
                        ['sku' => $sku],
                        [
                            'external_id' => $externalId ?: null,
                            'category_id' => $categoryId,
                            'name'        => $name,
                            'synced_at'   => Carbon::now(),
                        ]
                    );

                    // --- اگر variety داریم: آنها را در جدول جدا ذخیره کن
                    if (count($varieties)) {
                        $rows = [];
                        $keepKeys = []; // برای حذف مدل‌هایی که دیگر در CRM نیستند

                        foreach ($varieties as $v) {
                            $varietyId  = Arr::get($v, 'id');           // اگر CRM این فیلد را دارد
                            $uniqueKey  = Arr::get($v, 'unique_key');   // اگر CRM می‌دهد
                            $modelName  = (string) (Arr::get($v, 'model_name') ?? Arr::get($v, 'title') ?? 'unknown');
                            $sellPrice  = (int) (Arr::get($v, 'price') ?? 0);
                            $qty        = (int) (Arr::get($v, 'quantity') ?? 0);

                            // کلید تشخیص یکتا: variety_id اگر هست، وگرنه unique_key
                            $identity = $varietyId ? ('vid:' . $varietyId) : ('uk:' . (string)$uniqueKey);
                            $keepKeys[] = $identity;

                            $rows[] = [
                                'product_id'   => $product->id,
                                'variant_name' => $modelName,
                                'variety_id'   => $varietyId,
                                'unique_key'   => $uniqueKey,
                                'sell_price'   => max(0, $sellPrice),
                                'buy_price'    => null, // اگر CRM buy_price دارد اینجا map کن
                                'stock'        => max(0, $qty),
                                'reserved'     => 0,
                                'synced_at'    => Carbon::now(),
                                'created_at'   => Carbon::now(),
                                'updated_at'   => Carbon::now(),
                            ];
                        }

                        // upsert با کلید درست
                        // اگر variety_id همیشه هست:
                        ProductVariant::upsert(
                            $rows,
                            ['product_id', 'variety_id'],
                            ['variant_name','unique_key','sell_price','buy_price','stock','reserved','synced_at','updated_at']
                        );

                        // اگر variety_id ممکنه null باشه، بهتره دو مسیر بذاری:
                        // 1) آنهایی که variety_id دارند را با کلید variety_id upsert
                        // 2) آنهایی که variety_id ندارند را با کلید unique_key upsert
                        // (اگه خواستی، همین را برات دقیق‌تر می‌کنم.)

                        // محاسبه قیمت/موجودی محصول از روی variants
                        $stock = ProductVariant::where('product_id', $product->id)->sum('stock');
                        $minPrice = ProductVariant::where('product_id', $product->id)->min('sell_price');

                        $product->update([
                            'stock' => max(0, (int)$stock),
                            'price' => max(0, (int)($minPrice ?? $basePrice)),
                        ]);

                        // (اختیاری) پاک کردن مدل‌هایی که دیگر در CRM نیستند
                        // اینجا چون کلید ترکیبی پیچیده‌ست، معمولاً با variety_id انجام می‌دن:
                        // ProductVariant::where('product_id',$product->id)->whereNotIn('variety_id',$varietyIds)->delete();

                    } else {
                        // --- اگر variety نداریم: خود محصول ساده است
                        $product->update([
                            'stock' => max(0, $baseQty),
                            'price' => max(0, $basePrice),
                        ]);

                        // (اختیاری) اگر قبلاً variant داشت، پاکش کن:
                        // ProductVariant::where('product_id',$product->id)->delete();
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

        return compact('created', 'updated', 'failed');
    }
}
