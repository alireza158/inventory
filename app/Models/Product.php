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
        $search = static::normalizeProductSearchTerm((string) $q);

        if ($search === '') {
            return $query;
        }

        $tokens = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $isCodeLike = static::isProductSearchCodeLike($search);

        $query->where(function (Builder $productQuery) use ($search, $tokens, $isCodeLike) {
            static::orWhereProductCodeMatches($productQuery, $search, $isCodeLike);

            if ($isCodeLike || count($tokens) === 1) {
                $patterns = static::buildProductSearchPatterns($search);

                foreach (static::productSearchableColumns() as $column) {
                    static::orWhereProductSearchColumnLikeAny($productQuery, 'products.' . $column, $patterns);
                }

                $productQuery->orWhereHas('category', function (Builder $categoryQuery) use ($patterns) {
                    static::orWhereProductSearchColumnLikeAny($categoryQuery, 'categories.name', $patterns);
                });

                return;
            }

            static::orWhereNormalizedLike($productQuery, 'products.name', $search);
            static::orWhereAllTokensInColumn($productQuery, 'products.name', $tokens);

            if (static::hasProductColumnFromQualifiedName('products.description')) {
                static::orWhereNormalizedLike($productQuery, 'products.description', $search);
            }
        });

        return static::applyProductSearchScore($query, $search, $tokens);
    }

    private static function applyProductSearchScore(Builder $query, string $search, array $tokens): Builder
    {
        $name = static::normalizedSqlExpression('products.name');
        $searchLower = mb_strtolower($search);
        $compactSearch = str_replace(' ', '', $searchLower);
        $isCodeLike = static::isProductSearchCodeLike($search);

        $scoreSql = '0';
        $bindings = [];

        foreach (['products.barcode', 'products.sku', 'products.code', 'products.short_barcode'] as $column) {
            if (! static::hasProductColumnFromQualifiedName($column)) {
                continue;
            }

            $codeExpression = static::normalizedSqlExpression($column);
            $scoreSql .= " + CASE WHEN {$codeExpression} = ? THEN " . ($isCodeLike ? 1000 : 240) . " ELSE 0 END";
            $bindings[] = $searchLower;
        }

        $scoreSql .= " + CASE WHEN {$name} = ? THEN 800 ELSE 0 END";
        $bindings[] = $searchLower;
        $scoreSql .= " + CASE WHEN {$name} LIKE ? THEN 650 ELSE 0 END";
        $bindings[] = static::escapeProductSearchLike($searchLower) . '%';
        $scoreSql .= " + CASE WHEN {$name} LIKE ? THEN 360 ELSE 0 END";
        $bindings[] = '%' . static::escapeProductSearchLike($searchLower) . '%';
        $scoreSql .= " + CASE WHEN REPLACE({$name}, ' ', '') LIKE ? THEN 220 ELSE 0 END";
        $bindings[] = '%' . static::escapeProductSearchLike($compactSearch) . '%';

        if (count($tokens) > 1) {
            $allTokensConditions = [];

            foreach ($tokens as $token) {
                $allTokensConditions[] = "{$name} LIKE ?";
                $bindings[] = '%' . static::escapeProductSearchLike(mb_strtolower($token)) . '%';
            }

            $scoreSql .= ' + CASE WHEN ' . implode(' AND ', $allTokensConditions) . ' THEN 900 ELSE 0 END';
        }

        foreach ($tokens as $token) {
            $token = mb_strtolower($token);
            $scoreSql .= " + CASE WHEN {$name} LIKE ? THEN 90 ELSE 0 END";
            $bindings[] = '%' . static::escapeProductSearchLike($token) . '%';
        }

        $categoryName = static::normalizedSqlExpression('categories.name');
        $query->leftJoin('categories as search_categories', 'search_categories.id', '=', 'products.category_id');
        $categoryName = str_replace('categories.', 'search_categories.', $categoryName);
        $scoreSql .= " + CASE WHEN {$categoryName} LIKE ? THEN 35 ELSE 0 END";
        $bindings[] = '%' . static::escapeProductSearchLike($searchLower) . '%';

        if (static::hasProductColumnFromQualifiedName('products.description')) {
            $description = static::normalizedSqlExpression('products.description');
            $scoreSql .= " + CASE WHEN {$description} LIKE ? THEN 20 ELSE 0 END";
            $bindings[] = '%' . static::escapeProductSearchLike($searchLower) . '%';
        }

        return $query
            ->select('products.*')
            ->orderByRaw("({$scoreSql}) DESC", $bindings)
            ->orderByDesc('products.id');
    }




    private static function isProductSearchCodeLike(string $search): bool
    {
        return preg_match('/^[\p{L}\d\-_\.\/]+$/u', $search) === 1
            && (preg_match('/\d/u', $search) === 1 || mb_strlen($search) <= 16);
    }

    private static function orWhereProductCodeMatches(Builder $query, string $search, bool $isCodeLike)
    {
        if (! $isCodeLike) {
            return;
        }

        $query->orWhere(function (Builder $codeQuery) use ($search) {
            $searchLower = mb_strtolower($search);

            foreach (['products.barcode', 'products.sku', 'products.code', 'products.short_barcode'] as $column) {
                if (! static::hasProductColumnFromQualifiedName($column)) {
                    continue;
                }

                $expression = static::normalizedSqlExpression($column);
                $codeQuery->orWhereRaw("{$expression} = ?", [$searchLower]);
                $codeQuery->orWhereRaw("{$expression} LIKE ?", ['%' . static::escapeProductSearchLike($searchLower) . '%']);
            }
        });

        return static::applyProductSearchScore($query, $search, $tokens);
    }

    private static function orWhereNormalizedLike(Builder $query, string $column, string $search)
    {
        $expression = static::normalizedSqlExpression($column);
        $query->orWhereRaw("{$expression} LIKE ?", ['%' . static::escapeProductSearchLike(mb_strtolower($search)) . '%']);
    }

    private static function orWhereAllTokensInColumn(Builder $query, string $column, array $tokens)
    {
        $expression = static::normalizedSqlExpression($column);

        $query->orWhere(function (Builder $tokenQuery) use ($expression, $tokens) {
            foreach ($tokens as $token) {
                $tokenQuery->whereRaw("{$expression} LIKE ?", ['%' . static::escapeProductSearchLike(mb_strtolower($token)) . '%']);
            }
        });
    }

    public static function normalizeProductSearchTerm(string $term): string
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

    private static function buildProductSearchPatterns(string $term): array
    {
        $normalized = static::normalizeProductSearchTerm($term);

        if ($normalized === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $loosePattern = '%' . implode('%', array_map([static::class, 'escapeProductSearchLike'], $tokens)) . '%';
        $exactPattern = '%' . static::escapeProductSearchLike($normalized) . '%';

        return collect([$exactPattern, $loosePattern])
            ->flatMap(fn (string $pattern) => static::productSearchPersianArabicVariants($pattern))
            ->unique()
            ->values()
            ->all();
    }

    private static function productSearchPersianArabicVariants(string $pattern): array
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

    private static function escapeProductSearchLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }

    private static function hasProductColumnFromQualifiedName(string $column): bool
    {
        return str_starts_with($column, 'products.') && Schema::hasColumn('products', substr($column, 9));
    }

    private static function productSearchableColumns(): array
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

    private static function orWhereProductSearchColumnLikeAny(Builder $query, string $column, array $patterns)
    {
        $query->orWhere(function (Builder $columnQuery) use ($column, $patterns) {
            foreach ($patterns as $pattern) {
                $columnQuery->orWhere($column, 'like', $pattern);
            }
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

    private static function whereNormalizedLike(Builder $query, string $column, string $search)
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
