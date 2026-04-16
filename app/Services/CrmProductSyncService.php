<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class CrmProductSyncService
{
    private array $columnCache = [];

    public function sync(): array
    {
        $url = $this->getProductsUrl();

        $request = Http::timeout(60)
            ->retry(3, 500)
            ->acceptJson();

        $token = (string) config('services.ariya_crm.token', '');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $created = 0;
        $updated = 0;
        $failed  = 0;

        $nextUrl = $url;

        while ($nextUrl) {
            $payload = $request->get($nextUrl)->throw()->json();

            $items = Arr::get($payload, 'data.products.data', []);
            if (!is_array($items)) {
                $items = [];
            }

            foreach ($items as $item) {
                try {
                    if (Arr::get($item, 'status') !== 'available') {
    continue;
}
                    $result = DB::transaction(function () use ($item) {
                        return $this->syncSingleProduct((array) $item);
                    });

                    if ($result === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (\Throwable $e) {
                    $failed++;

                    Log::error('CRM product sync failed', [
                        'crm_product_id' => Arr::get($item, 'id'),
                        'title'          => Arr::get($item, 'title'),
                        'message'        => $e->getMessage(),
                        'trace'          => $e->getTraceAsString(),
                    ]);
                }
            }

            // pagination واقعی را از چند مسیر ممکن چک می‌کنیم
            $nextUrl =
                Arr::get($payload, 'data.products.next_page_url')
                ?? Arr::get($payload, 'data.next_page_url')
                ?? Arr::get($payload, 'links.next')
                ?? null;

            if (is_string($nextUrl) && $nextUrl !== '' && !str_starts_with($nextUrl, 'http')) {
                $base = rtrim((string) config('services.ariya_crm.base_url', 'https://api.ariyajanebi.ir'), '/');
                $nextUrl = $base . '/' . ltrim($nextUrl, '/');
            }

            if (!is_string($nextUrl) || $nextUrl === '') {
                $nextUrl = null;
            }
        }

        return compact('created', 'updated', 'failed');
    }

    private function syncSingleProduct(array $item): string
    {
        $externalId = trim((string) Arr::get($item, 'id', ''));
        if ($externalId === '') {
            throw new RuntimeException('CRM product id is missing.');
        }

        $name = trim((string) Arr::get($item, 'title', ''));
        if ($name === '') {
            $name = 'CRM Product ' . $externalId;
        }

        $varieties = Arr::get($item, 'varieties', []);
        if (!is_array($varieties)) {
            $varieties = [];
        }

        $basePrice = $this->extractProductPrice($item, $varieties);
        $baseQty   = $this->extractProductQuantity($item, $varieties);

        $category = $this->resolveDefaultCategory();
        $sku      = 'ARIYA-' . $externalId;

        $product = Product::query()
            ->lockForUpdate()
            ->where('sku', $sku)
            ->first();

        
            [$productCode6, $seq4] = $this->generateProductCode($category);

            $product = new Product();
            
            $product->sku           = $sku;
            $product->code          = $productCode6;
            $product->short_barcode = $seq4;
            $product->stock         = 0;
            $product->price         = 0;
       

        $product->category_id = $category->id;
        $product->name        = $name;
dd($product->name );
        if ($this->hasColumn('products', 'external_id')) {
            $product->external_id = $externalId;
        }

        if ($this->hasColumn('products', 'is_sellable')) {
            $product->is_sellable = ((string) Arr::get($item, 'status', 'available')) === 'available';
        }

        if ($this->hasColumn('products', 'synced_at')) {
            $product->synced_at = Carbon::now();
        }

        $product->save();

        if (count($varieties) > 0) {
            $this->syncProductVariants($product, $varieties);
        } else {
            $this->syncBaseVariant($product, $basePrice, $baseQty);
        }

        $this->recalcProductSummary($product, $basePrice);

        return $isNew ? 'created' : 'updated';
    }

    private function syncProductVariants(Product $product, array $varieties): void
    {
        $existingVariants = ProductVariant::query()
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->get();

        $existingByIdentity = [];
        $reservedDesign2 = [];

        foreach ($existingVariants as $variant) {
            $identity = $this->variantIdentityFromExisting($variant);
            if ($identity !== null && !isset($existingByIdentity[$identity])) {
                $existingByIdentity[$identity] = $variant;
            }

            $design2 = $this->extractDesign2FromVariant($variant);
            if ($design2 !== null) {
                $reservedDesign2[$design2] = true;
            }
        }

        $keptIds = [];

        foreach (array_values($varieties) as $index => $variety) {
            $variety = (array) $variety;

            $identity = $this->variantIdentityFromPayload($variety, $index);
            $existing = $existingByIdentity[$identity] ?? null;

            if ($existing) {
                $design2 = $this->extractDesign2FromVariant($existing) ?: $this->allocateDesign2($reservedDesign2);
            } else {
                $design2 = $this->allocateDesign2($reservedDesign2);
            }

            $reservedDesign2[$design2] = true;

            $title = $this->resolveVariantTitle($variety, $index);

            $sellPrice = $this->extractVariantPrice($variety);
            $buyPrice  = $this->extractVariantBuyPrice($variety);
            $stock     = max(0, (int) (Arr::get($variety, 'quantity') ?? 0));

            $payload = [
                'product_id'    => $product->id,
                'model_list_id' => null,
                'variant_name'  => trim($product->name . ' ' . $title),
                'variety_name'  => $title,
                'variety_code'  => str_pad((string) ((int) $design2), 4, '0', STR_PAD_LEFT),
                'variant_code'  => $this->buildVariantCode11((string) $product->code, '000', $design2, $existing?->id),
                'sell_price'    => $sellPrice,
                'buy_price'     => $buyPrice,
                'stock'         => $stock,
                'reserved'      => $existing?->reserved ?? 0,
            ];

            if ($this->hasColumn('product_variants', 'is_active')) {
                $payload['is_active'] = true;
            }

            if ($this->hasColumn('product_variants', 'synced_at')) {
                $payload['synced_at'] = Carbon::now();
            }

            if ($this->hasColumn('product_variants', 'variety_id')) {
                $payload['variety_id'] = Arr::get($variety, 'id') ?: null;
            }

            if ($this->hasColumn('product_variants', 'unique_key')) {
                $uniqueKey = trim((string) (
                    Arr::get($variety, 'unique_attributes_key', '')
                    ?: Arr::get($variety, 'id', '')
                ));
                $payload['unique_key'] = $uniqueKey !== '' ? $uniqueKey : null;
            }

            if ($existing) {
                $existing->update($payload);
                $keptIds[] = $existing->id;
            } else {
                $newVariant = ProductVariant::create($payload);
                $keptIds[] = $newVariant->id;
            }
        }

        $staleQuery = ProductVariant::query()->where('product_id', $product->id);

        if (count($keptIds) > 0) {
            $staleQuery->whereNotIn('id', $keptIds);
        }

        $staleUpdate = [
            'stock' => 0,
        ];

        if ($this->hasColumn('product_variants', 'is_active')) {
            $staleUpdate['is_active'] = false;
        }

        if ($this->hasColumn('product_variants', 'synced_at')) {
            $staleUpdate['synced_at'] = Carbon::now();
        }

        $staleQuery->update($staleUpdate);
    }

    private function syncBaseVariant(Product $product, int $basePrice, int $baseQty): void
    {
        $baseVariantCode = $product->code . '00000';

        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where(function ($q) use ($baseVariantCode) {
                $q->where('variant_code', $baseVariantCode)
                    ->orWhere('variety_code', '0000');
            })
            ->lockForUpdate()
            ->first();

        if (!$variant) {
            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->orderBy('id')
                ->first();
        }

        $payload = [
            'product_id'    => $product->id,
            'model_list_id' => null,
            'variant_name'  => $product->name,
            'variety_name'  => '—',
            'variety_code'  => '0000',
            'variant_code'  => $this->buildVariantCode11((string) $product->code, '000', '00', $variant?->id),
            'sell_price'    => max(0, $basePrice),
            'buy_price'     => null,
            'stock'         => max(0, $baseQty),
            'reserved'      => $variant?->reserved ?? 0,
        ];

        if ($this->hasColumn('product_variants', 'is_active')) {
            $payload['is_active'] = true;
        }

        if ($this->hasColumn('product_variants', 'synced_at')) {
            $payload['synced_at'] = Carbon::now();
        }

        if ($variant) {
            $variant->update($payload);
        } else {
            $variant = ProductVariant::create($payload);
        }

        $disableOthers = [
            'stock' => 0,
        ];

        if ($this->hasColumn('product_variants', 'is_active')) {
            $disableOthers['is_active'] = false;
        }

        if ($this->hasColumn('product_variants', 'synced_at')) {
            $disableOthers['synced_at'] = Carbon::now();
        }

        ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('id', '!=', $variant->id)
            ->update($disableOthers);
    }

    private function recalcProductSummary(Product $product, int $fallbackPrice = 0): void
    {
        $stock = (int) ProductVariant::query()
            ->where('product_id', $product->id)
            ->sum('stock');

        $priceQuery = ProductVariant::query()
            ->where('product_id', $product->id);

        if ($this->hasColumn('product_variants', 'is_active')) {
            $priceQuery->where('is_active', true);
        }

        $minPrice = $priceQuery->min('sell_price');

        $product->update([
            'stock' => max(0, $stock),
            'price' => max(0, (int) ($minPrice ?? $fallbackPrice)),
        ]);
    }

    private function extractProductPrice(array $item, array $varieties): int
    {
        $price = Arr::get($item, 'major_final_price.amount')
            ?? Arr::get($item, 'price')
            ?? 0;

        $price = (int) $price;

        if ($price > 0) {
            return $price;
        }

        $variantMin = collect($varieties)
            ->map(fn ($v) => $this->extractVariantPrice((array) $v))
            ->filter(fn ($v) => $v > 0)
            ->min();

        return max(0, (int) ($variantMin ?? 0));
    }

    private function extractProductQuantity(array $item, array $varieties): int
    {
        if (count($varieties) > 0) {
            return max(0, (int) collect($varieties)->sum(function ($v) {
                return (int) Arr::get((array) $v, 'quantity', 0);
            }));
        }

        return 0;
    }

    private function extractVariantPrice(array $variety): int
    {
        return max(0, (int) (
            Arr::get($variety, 'final_price.amount')
            ?? Arr::get($variety, 'price')
            ?? 0
        ));
    }

    private function extractVariantBuyPrice(array $variety): ?int
    {
        $buyPrice = Arr::get($variety, 'purchase_price');

        if ($buyPrice === null || $buyPrice === '') {
            return null;
        }

        return max(0, (int) $buyPrice);
    }

    private function resolveVariantTitle(array $variety, int $index): string
    {
        $title = trim((string) (
            Arr::get($variety, 'name')
            ?? Arr::get($variety, 'color.name')
            ?? ''
        ));

        if ($title !== '') {
            return $title;
        }

        $attrs = Arr::get($variety, 'attributes', []);
        if (is_array($attrs) && count($attrs) > 0) {
            $parts = [];

            foreach ($attrs as $attr) {
                $attr = (array) $attr;
                $label = trim((string) (
                    Arr::get($attr, 'pivot.value')
                    ?? Arr::get($attr, 'value')
                    ?? Arr::get($attr, 'name')
                    ?? ''
                ));

                if ($label !== '') {
                    $parts[] = $label;
                }
            }

            if (count($parts) > 0) {
                return implode(' - ', $parts);
            }
        }

        return 'تنوع ' . ($index + 1);
    }

    private function resolveDefaultCategory(): Category
    {
        $categoryId = (int) config('services.ariya_crm.default_category_id', 1);

        $category = Category::query()
            ->lockForUpdate()
            ->find($categoryId);

        if (!$category) {
            throw new RuntimeException("Default category not found. category_id={$categoryId}");
        }

        if ($this->normalizeCategory2($category->code) === null) {
            throw new RuntimeException("Category code must be 2 digits. category_id={$category->id}");
        }

        return $category;
    }

    private function ensureProductCodeIntegrity(Product $product, Category $category): void
    {
        $cat2 = $this->normalizeCategory2($category->code);

        $currentCode = (string) ($product->code ?? '');
        $currentSeq4 = (string) ($product->short_barcode ?? '');

        $codeIsValid = preg_match('/^\d{6}$/', $currentCode) === 1;
        $seqIsValid  = preg_match('/^\d{4}$/', $currentSeq4) === 1;

        if (!$codeIsValid && $seqIsValid) {
            $product->code = $cat2 . $currentSeq4;
            return;
        }

        if ($codeIsValid && !$seqIsValid) {
            $product->short_barcode = substr($currentCode, -4);
            return;
        }

        if (!$codeIsValid && !$seqIsValid) {
            [$productCode6, $seq4] = $this->generateProductCode($category);
            $product->code = $productCode6;
            $product->short_barcode = $seq4;
        }
    }

    private function generateProductCode(Category $category): array
    {
        $cat2 = $this->normalizeCategory2($category->code);
        $seq4 = $this->nextProductSeq4();

        return [$cat2 . $seq4, $seq4];
    }

    private function nextProductSeq4(): string
    {
        $mx = DB::table('products')
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(COALESCE(NULLIF(short_barcode,''), SUBSTRING(code, 3, 4)) AS UNSIGNED)) as mx")
            ->value('mx');

        $next = ((int) $mx) + 1;

        if ($next < 1) {
            $next = 1;
        }

        if ($next > 9999) {
            throw new RuntimeException('حداکثر 9999 کالا پشتیبانی می‌شود.');
        }

        return str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function buildVariantCode11(string $productCode6, string $model3, string $design2, ?int $ignoreId = null): string
    {
        $model3  = $this->normalizeModel3($model3);
        $design2 = preg_match('/^\d{2}$/', $design2) ? $design2 : '00';

        $code = $productCode6 . $model3 . $design2;

        $exists = ProductVariant::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('variant_code', $code)
            ->exists();

        if ($exists) {
            throw new RuntimeException("کد تنوع تکراری است: {$code}");
        }

        return $code;
    }

    private function variantIdentityFromPayload(array $variety, int $index): string
    {
        $varietyId = trim((string) Arr::get($variety, 'id', ''));
        if ($varietyId !== '') {
            return 'variety_id:' . $varietyId;
        }

        $uniqueKey = trim((string) Arr::get($variety, 'unique_attributes_key', ''));
        if ($uniqueKey !== '') {
            return 'unique_key:' . $uniqueKey;
        }

        $title = $this->resolveVariantTitle($variety, $index);

        return 'name:' . $this->normalizeIdentityString($title);
    }

    private function variantIdentityFromExisting(ProductVariant $variant): ?string
    {
        if ($this->hasColumn('product_variants', 'variety_id')) {
            $varietyId = trim((string) $variant->getAttribute('variety_id'));
            if ($varietyId !== '') {
                return 'variety_id:' . $varietyId;
            }
        }

        if ($this->hasColumn('product_variants', 'unique_key')) {
            $uniqueKey = trim((string) $variant->getAttribute('unique_key'));
            if ($uniqueKey !== '') {
                return 'unique_key:' . $uniqueKey;
            }
        }

        $title = trim((string) ($variant->variety_name ?: $variant->variant_name ?: ''));
        if ($title === '') {
            return null;
        }

        return 'name:' . $this->normalizeIdentityString($title);
    }

    private function extractDesign2FromVariant(ProductVariant $variant): ?string
    {
        $variantCode = (string) ($variant->variant_code ?? '');
        if (preg_match('/^\d{11}$/', $variantCode)) {
            return substr($variantCode, -2);
        }

        $varietyCode = (string) ($variant->variety_code ?? '');
        if (preg_match('/^\d{4}$/', $varietyCode)) {
            return substr($varietyCode, -2);
        }

        return null;
    }

    private function allocateDesign2(array $reservedDesign2): string
    {
        for ($i = 1; $i <= 99; $i++) {
            $d = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            if (!isset($reservedDesign2[$d])) {
                return $d;
            }
        }

        throw new RuntimeException('برای این محصول بیشتر از 99 تنوع قابل پشتیبانی نیست.');
    }

    private function normalizeCategory2(?string $code): ?string
    {
        $c = trim((string) ($code ?? ''));
        if ($c === '') {
            return null;
        }

        if (!preg_match('/^\d{2}$/', $c)) {
            return null;
        }

        return $c;
    }

    private function normalizeModel3(?string $code): string
    {
        $c = preg_replace('/\D+/', '', (string) ($code ?? ''));
        $c = substr($c, 0, 3);

        return str_pad($c, 3, '0', STR_PAD_LEFT);
    }

    private function normalizeIdentityString(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/\s+/u', ' ', $value);

        return $value;
    }

    private function getProductsUrl(): string
    {
        $direct = trim((string) config('services.ariya_crm.products_url', ''));
        if ($direct !== '') {
            return $direct;
        }

        $base = rtrim((string) config('services.ariya_crm.base_url', 'https://api.ariyajanebi.ir'), '/');

        return $base . '/v1/front/products';
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;

        if (!array_key_exists($key, $this->columnCache)) {
            $this->columnCache[$key] = Schema::hasColumn($table, $column);
        }

        return $this->columnCache[$key];
    }
}