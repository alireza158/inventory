<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\ProductVariantStructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category_id',
        'image_path',

        'code',
        'short_barcode',
        'barcode',

        // خلاصه‌ی محاسبه‌شده از variants (برای لیست/فیلتر)
        'stock',
        'reserved',
        'unit',

        // خلاصه‌ی محاسبه‌شده از variants (مثلاً کمترین قیمت فروش)
        'price',

        // اختیاری/قدیمی
        'sale_retail',
        'sale_wholesale',
        'buy_retail',
        'buy_wholesale',
        'color',

        // CRM
        'external_id',
        'synced_at',

        // اگر ستونش هست و نمی‌خوای حذف کنی:
        'models',
        'has_colors',
        'is_sellable',
        'warehouse_zone',
        'warehouse_rows',
        'warehouse_bins',
    ];

    protected $casts = [
        'stock'      => 'integer',
        'reserved'   => 'integer',
        'price'      => 'integer',

        'sale_retail'     => 'integer',
        'sale_wholesale'  => 'integer',
        'buy_retail'      => 'integer',
        'buy_wholesale'   => 'integer',

        'models'     => 'array',
        'has_colors' => 'boolean',
        'is_sellable' => 'boolean',
        'warehouse_zone' => 'integer',
        'warehouse_rows' => 'array',
        'warehouse_bins' => 'array',
        'synced_at'  => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function validVariants()
    {
        return app(ProductVariantStructureService::class)->applyValidConstraints($this->variants(), $this);
    }

    public function validVariantCollection(bool $activeOnly = true)
    {
        return app(ProductVariantStructureService::class)->validVariants($this, $activeOnly);
    }

    public function invalidVariantCollection()
    {
        return app(ProductVariantStructureService::class)->invalidVariants($this);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) ($this->stock ?? 0));
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    public function scopeSearch(Builder $query, $q): Builder
    {
        $patterns = static::searchPatterns((string) $q);

        if ($patterns === []) {
            return $query;
        }

        return $query->where(function (Builder $productQuery) use ($patterns) {
            foreach (static::searchableColumns() as $column) {
                static::orWhereColumnLikeAny($productQuery, 'products.' . $column, $patterns);
            }

            $productQuery->orWhereHas('variants', function (Builder $variantQuery) use ($patterns) {
                foreach (['variant_name', 'variant_code', 'variety_name', 'variety_code', 'unique_key'] as $column) {
                    static::orWhereColumnLikeAny($variantQuery, 'product_variants.' . $column, $patterns);
                }
            });
        });
    }

    private static function normalizeProductSearchTerm(string $term): string
    {
        $term = strtr($term, [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            "\u{200C}" => ' ',
            "\u{200D}" => ' ',
            "\u{0640}" => '',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', $term));
    }

    private static function searchPatterns(string $term): array
    {
        $normalized = static::normalizeProductSearchTerm($term);

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $loosePattern = '%' . implode('%', array_map([static::class, 'escapeLike'], $tokens)) . '%';
        $exactPattern = '%' . static::escapeLike($normalized) . '%';

        return collect([$exactPattern, $loosePattern])
            ->flatMap(fn (string $pattern) => static::persianArabicPatternVariants($pattern))
            ->unique()
            ->values()
            ->all();
    }

    private static function persianArabicPatternVariants(string $pattern): array
    {
        $patterns = [$pattern];

        foreach ([['ی', 'ي'], ['ک', 'ك']] as [$persian, $arabic]) {
            $patterns = collect($patterns)
                ->flatMap(fn (string $item) => [$item, str_replace($persian, $arabic, $item)])
                ->unique()
                ->values()
                ->all();
        }

        return $patterns;
    }

    private static function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private static function searchableColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        return $columns = collect(['name', 'code', 'sku', 'barcode', 'short_barcode', 'description'])
            ->filter(fn (string $column) => Schema::hasColumn('products', $column))
            ->values()
            ->all();
    }

    private static function orWhereColumnLikeAny(Builder $query, string $column, array $patterns): void
    {
        $query->orWhere(function (Builder $columnQuery) use ($column, $patterns) {
            foreach ($patterns as $pattern) {
                $columnQuery->orWhere($column, 'like', $pattern);
            }
        });
    }
}
