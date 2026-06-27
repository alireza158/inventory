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
        $search = static::normalizeSearchTerm((string) $q);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $productQuery) use ($search) {
            foreach (static::searchableColumns() as $column) {
                $productQuery->orWhere(function (Builder $columnQuery) use ($column, $search) {
                    static::whereNormalizedLike($columnQuery, 'products.' . $column, $search);
                });
            }

            $productQuery->orWhereHas('variants', function (Builder $variantQuery) use ($search) {
                foreach (['variant_name', 'variant_code', 'variety_name', 'variety_code', 'unique_key'] as $column) {
                    $variantQuery->orWhere(function (Builder $columnQuery) use ($column, $search) {
                        static::whereNormalizedLike($columnQuery, 'product_variants.' . $column, $search);
                    });
                }
            });
        });
    }

    public static function normalizeSearchTerm(string $term): string
    {
        return trim(strtr($term, [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            "\u{200C}" => ' ',
            "\u{200D}" => ' ',
            "\u{0640}" => '',
        ]));
    }

    private static function searchableColumns(): array
    {
        return collect(['name', 'code', 'sku', 'barcode', 'short_barcode', 'description'])
            ->filter(fn (string $column) => Schema::hasColumn('products', $column))
            ->values()
            ->all();
    }

    private static function whereNormalizedLike(Builder $query, string $column, string $search): void
    {
        $expression = static::normalizedSqlExpression($column);
        $compactExpression = "REPLACE({$expression}, ' ', '')";
        $compactSearch = str_replace(' ', '', $search);

        $query->whereRaw("{$expression} LIKE ?", ['%' . mb_strtolower($search) . '%']);

        $query->orWhereRaw("{$compactExpression} LIKE ?", ['%' . mb_strtolower($compactSearch) . '%']);
    }

    private static function normalizedSqlExpression(string $column): string
    {
        $expression = "LOWER(COALESCE({$column}, ''))";

        foreach ([
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ة' => 'ه',
            'ۀ' => 'ه',
            "\u{200C}" => ' ',
            "\u{200D}" => ' ',
            "\u{0640}" => '',
        ] as $from => $to) {
            $expression = "REPLACE({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }
}
