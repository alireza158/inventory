<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductExportService
{
    public const LOW_STOCK_THRESHOLD = 5;

    public function query(array $filters): Builder
    {
        $warehouseId = $filters['warehouse_id'] ?? null;

        return Product::query()
            ->with('category')
            ->withSum(['warehouseStocks as export_stock' => function ($query) use ($warehouseId) {
                if ($warehouseId) {
                    $query->where('warehouse_id', (int) $warehouseId);
                }
            }], 'quantity')
            ->when(!empty($filters['category_id']), fn (Builder $query) => $query->where('category_id', (int) $filters['category_id']))
            ->when(!empty($filters['search']), function (Builder $query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(function (Builder $q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('short_barcode', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');
    }

    public function filteredProducts(array $filters): Collection
    {
        $products = $this->query($filters)->get();
        $status = $filters['stock_status'] ?? 'all';

        return $products->filter(function (Product $product) use ($status) {
            $stock = $this->stock($product);

            return match ($status) {
                'in_stock' => $stock > 0,
                'out_of_stock' => $stock <= 0,
                'low_stock' => $stock > 0 && $stock <= self::LOW_STOCK_THRESHOLD,
                default => true,
            };
        })->values();
    }

    public function rows(array $filters): array
    {
        return $this->filteredProducts($filters)->map(fn (Product $product) => $this->row($product))->all();
    }

    public function row(Product $product): array
    {
        $stock = $this->stock($product);

        return [
            'id' => $product->id,
            'image_url' => $this->imageUrl($product),
            'pdf_image_src' => $this->pdfImageSrc($product),
            'name' => $product->name,
            'sku' => $product->sku ?: ($product->code ?: '—'),
            'category' => $product->category?->name ?: 'بدون دسته‌بندی',
            'stock' => $stock,
            'price' => (int) ($product->price ?? $product->sale_retail ?? 0),
            'stock_status' => $this->stockStatus($stock),
            'stock_status_class' => $this->stockStatusClass($stock),
            'updated_at' => optional($product->updated_at)->format('Y/m/d H:i'),
            'unit' => $product->unit ?: 'عدد',
            'barcode' => $product->barcode ?: ($product->short_barcode ?: ''),
        ];
    }

    public function meta(array $filters): array
    {
        $category = !empty($filters['category_id']) ? Category::find($filters['category_id']) : null;
        $warehouse = !empty($filters['warehouse_id']) ? Warehouse::find($filters['warehouse_id']) : null;

        return [
            'category' => $category?->name ?? 'همه دسته‌بندی‌ها',
            'warehouse' => $warehouse?->name ?? 'همه انبارها',
            'stock_status' => $this->stockStatusFilterLabel($filters['stock_status'] ?? 'all'),
            'search' => $filters['search'] ?? '',
            'exported_at' => now()->format('Y/m/d H:i'),
            'store_name' => config('app.name', 'سامانه انبارداری'),
        ];
    }

    public function imageUrl(Product $product): string
    {
        $path = $this->localImagePath($product);

        if ($path) {
            return route('products.image', $product);
        }

        if ($this->isRemoteImage($product->image_path)) {
            return $product->image_path;
        }

        return $this->placeholderImage();
    }

    public function pdfImageSrc(Product $product): string
    {
        $path = $this->localImagePath($product);

        if ($path) {
            $absolutePath = Storage::disk('public')->path($path);
            $mime = @mime_content_type($absolutePath) ?: 'image/jpeg';
            $content = @file_get_contents($absolutePath);

            if ($content !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($content);
            }
        }

        if ($this->isRemoteImage($product->image_path)) {
            return $product->image_path;
        }

        return $this->placeholderImage();
    }

    private function localImagePath(Product $product): ?string
    {
        $imagePath = trim((string) ($product->image_path ?? ''));

        if ($imagePath === '' || $this->isRemoteImage($imagePath)) {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            ltrim($imagePath, '/'),
            preg_replace('#^/?storage/#', '', $imagePath),
            preg_replace('#^/?public/#', '', $imagePath),
            basename($imagePath) ? 'products/' . basename($imagePath) : null,
        ])));

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isRemoteImage(?string $path): bool
    {
        return is_string($path) && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'));
    }

    private function placeholderImage(): string
    {
        return 'data:image/png;base64,' . 'iVBORw0KGgoAAAANSUhEUgAAAFQAAABUCAYAAAAcaxDBAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAxUlEQVR4nO3QQQ3AIADAQMAJ/5yuwgOi8wKSpd1Z7z0AAAAAAAAAAAAAAAAAAAAAAAB8Z54H4GbEUYijEEchjkIchTgKcRTiKMTRiKMQRyGOQhyFOApxFOIoxNGIoxBHIY5CHIWE8c5zAF8rjiKMQhyFOApxFOIoxFGIoxBHIY5CHIWE8c5zAK8VjiKMQhyFOApxFOIoxFGIoxBHIY5CHIWE8c5zAG8VjiKMQhyFOApxFOIoxFGIoxBHIY5CHIWE8c5zAP8WAAAAAAAAAAAAAAAAAAAAAAAArB2lJwKTe+Ve3wAAAABJRU5ErkJggg==';
    }


    public static function pdfText(?string $text): string
    {
        $text = (string) $text;

        if ($text === '') {
            return '';
        }

        return preg_replace_callback('/[اآأإبپتثجچحخدذرزژسشصضطظعغفقکكگلمنوؤهةیيئء]+/u', function (array $matches): string {
            return self::shapeArabicRun($matches[0]);
        }, $text) ?? $text;
    }

    private static function shapeArabicRun(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $shaped = [];
        $count = count($chars);

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];
            $forms = self::arabicForms()[$char] ?? null;

            if (! $forms) {
                $shaped[] = $char;
                continue;
            }

            $previous = $chars[$i - 1] ?? null;
            $next = $chars[$i + 1] ?? null;
            $connectPrevious = $previous !== null && self::canConnectToPrevious($char) && self::canConnectToNext($previous);
            $connectNext = $next !== null && self::canConnectToNext($char) && self::canConnectToPrevious($next);

            $shaped[] = match (true) {
                $connectPrevious && $connectNext => $forms[3] ?? $forms[0],
                $connectPrevious => $forms[1] ?? $forms[0],
                $connectNext => $forms[2] ?? $forms[0],
                default => $forms[0],
            };
        }

        return implode('', array_reverse($shaped));
    }

    private static function canConnectToPrevious(string $char): bool
    {
        return isset(self::arabicForms()[$char]) && ! in_array($char, ['ا', 'آ', 'أ', 'إ', 'د', 'ذ', 'ر', 'ز', 'ژ', 'و', 'ؤ'], true);
    }

    private static function canConnectToNext(string $char): bool
    {
        return isset(self::arabicForms()[$char]);
    }

    private static function arabicForms(): array
    {
        return [
            'ا' => ['ﺍ', 'ﺎ', 'ﺍ', 'ﺎ'], 'آ' => ['ﺁ', 'ﺂ', 'ﺁ', 'ﺂ'], 'أ' => ['ﺃ', 'ﺄ', 'ﺃ', 'ﺄ'], 'إ' => ['ﺇ', 'ﺈ', 'ﺇ', 'ﺈ'],
            'ب' => ['ﺏ', 'ﺐ', 'ﺑ', 'ﺒ'], 'پ' => ['ﭖ', 'ﭗ', 'ﭘ', 'ﭙ'], 'ت' => ['ﺕ', 'ﺖ', 'ﺗ', 'ﺘ'], 'ث' => ['ﺙ', 'ﺚ', 'ﺛ', 'ﺜ'],
            'ج' => ['ﺝ', 'ﺞ', 'ﺟ', 'ﺠ'], 'چ' => ['ﭺ', 'ﭻ', 'ﭼ', 'ﭽ'], 'ح' => ['ﺡ', 'ﺢ', 'ﺣ', 'ﺤ'], 'خ' => ['ﺥ', 'ﺦ', 'ﺧ', 'ﺨ'],
            'د' => ['ﺩ', 'ﺪ', 'ﺩ', 'ﺪ'], 'ذ' => ['ﺫ', 'ﺬ', 'ﺫ', 'ﺬ'], 'ر' => ['ﺭ', 'ﺮ', 'ﺭ', 'ﺮ'], 'ز' => ['ﺯ', 'ﺰ', 'ﺯ', 'ﺰ'], 'ژ' => ['ﮊ', 'ﮋ', 'ﮊ', 'ﮋ'],
            'س' => ['ﺱ', 'ﺲ', 'ﺳ', 'ﺴ'], 'ش' => ['ﺵ', 'ﺶ', 'ﺷ', 'ﺸ'], 'ص' => ['ﺹ', 'ﺺ', 'ﺻ', 'ﺼ'], 'ض' => ['ﺽ', 'ﺾ', 'ﺿ', 'ﻀ'],
            'ط' => ['ﻁ', 'ﻂ', 'ﻃ', 'ﻄ'], 'ظ' => ['ﻅ', 'ﻆ', 'ﻇ', 'ﻈ'], 'ع' => ['ﻉ', 'ﻊ', 'ﻋ', 'ﻌ'], 'غ' => ['ﻍ', 'ﻎ', 'ﻏ', 'ﻐ'],
            'ف' => ['ﻑ', 'ﻒ', 'ﻓ', 'ﻔ'], 'ق' => ['ﻕ', 'ﻖ', 'ﻗ', 'ﻘ'], 'ک' => ['ﮎ', 'ﮏ', 'ﮐ', 'ﮑ'], 'ك' => ['ﻙ', 'ﻚ', 'ﻛ', 'ﻜ'],
            'گ' => ['ﮒ', 'ﮓ', 'ﮔ', 'ﮕ'], 'ل' => ['ﻝ', 'ﻞ', 'ﻟ', 'ﻠ'], 'م' => ['ﻡ', 'ﻢ', 'ﻣ', 'ﻤ'], 'ن' => ['ﻥ', 'ﻦ', 'ﻧ', 'ﻨ'],
            'و' => ['ﻭ', 'ﻮ', 'ﻭ', 'ﻮ'], 'ؤ' => ['ﺅ', 'ﺆ', 'ﺅ', 'ﺆ'], 'ه' => ['ﻩ', 'ﻪ', 'ﻫ', 'ﻬ'], 'ة' => ['ﺓ', 'ﺔ', 'ﺓ', 'ﺔ'],
            'ی' => ['ﯼ', 'ﯽ', 'ﯾ', 'ﯿ'], 'ي' => ['ﻱ', 'ﻲ', 'ﻳ', 'ﻴ'], 'ئ' => ['ﺉ', 'ﺊ', 'ﺋ', 'ﺌ'], 'ء' => ['ﺀ', 'ﺀ', 'ﺀ', 'ﺀ'],
        ];
    }

    public function stock(Product $product): int
    {
        return max(0, (int) ($product->export_stock ?? $product->stock ?? 0));
    }

    public function stockStatus(int $stock): string
    {
        if ($stock <= 0) {
            return 'ناموجود';
        }

        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            return 'کم‌موجودی';
        }

        return 'موجود';
    }

    public function stockStatusClass(int $stock): string
    {
        if ($stock <= 0) {
            return 'danger';
        }

        if ($stock <= self::LOW_STOCK_THRESHOLD) {
            return 'warning';
        }

        return 'success';
    }

    private function stockStatusFilterLabel(string $status): string
    {
        return match ($status) {
            'in_stock' => 'فقط کالاهای موجود',
            'out_of_stock' => 'فقط کالاهای ناموجود',
            'low_stock' => 'کالاهای کم‌موجودی',
            default => 'همه محصولات',
        };
    }
}
