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
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"><rect width="80" height="80" rx="16" fill="#eef2ff"/><text x="40" y="48" font-size="28" text-anchor="middle">📦</text></svg>');
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
