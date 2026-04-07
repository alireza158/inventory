<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDeactivationDocument;
use App\Models\Category;
use App\Models\ProductDeactivationDocumentItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Verta;
class ProductDeactivationDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductDeactivationDocument::query()
            ->with(['creator:id,name']);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('time_range')) {
            $days = match ((string) $request->time_range) {
                'today' => 0,
                '7d' => 7,
                '30d' => 30,
                default => null,
            };

            if (!is_null($days)) {
                $from = $days === 0 ? now()->startOfDay() : now()->subDays($days)->startOfDay();
                $query->where('created_at', '>=', $from);
            }
        }

        $documents = $query->latest('id')->paginate(20)->withQueryString();
        return view('product-deactivation-documents.index', compact('documents'));
    }

    public function create()
    {
        $products = Product::query()
            ->where(function ($query) {
                $query->where('is_sellable', true)
                    ->orWhereHas('variants', function ($v) {
                        $v->where('is_active', true);
                    });
            })
            ->with([
                'category:id,name,parent_id',
                'variants' => fn ($q) => $q->where('is_active', true)->orderBy('variant_name'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'is_sellable', 'category_id']);

        $categories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        $subcategories = Category::query()
            ->whereNotNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        return view('product-deactivation-documents.create', compact('products', 'categories', 'subcategories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'reason_text' => ['required', 'string', 'min:3', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.subcategory_id' => ['nullable', 'integer', 'exists:categories,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ], [
            'reason_text.required' => 'نوشتن دلیل غیرفعال‌سازی الزامی است.',
            'items.required' => 'حداقل یک ردیف کالا برای غیرفعال‌سازی وارد کنید.',
            'items.min' => 'حداقل یک ردیف کالا برای غیرفعال‌سازی وارد کنید.',
            'items.*.product_id.required' => 'انتخاب کالا برای هر ردیف الزامی است.',
        ]);

        DB::transaction(function () use ($data) {
            $firstItem = $data['items'][0];
            $firstProduct = Product::query()->whereKey((int) $firstItem['product_id'])->lockForUpdate()->firstOrFail();
            $firstVariant = null;
            $firstType = ProductDeactivationDocument::TYPE_PRODUCT;

            if (!empty($firstItem['variant_id'])) {
                $firstVariant = ProductVariant::query()
                    ->whereKey((int) $firstItem['variant_id'])
                    ->where('product_id', $firstProduct->id)
                    ->lockForUpdate()
                    ->first();
                $firstType = ProductDeactivationDocument::TYPE_VARIANT;
            }

            $doc = ProductDeactivationDocument::create([
                'document_number' => 'TMP-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'deactivation_type' => $firstType,
                'product_id' => $firstProduct->id,
                'variant_id' => $firstVariant?->id,
                'items_count' => count($data['items']),
                'reason_type' => 'custom',
                'reason_text' => trim((string) $data['reason_text']),
                'description' => null,
                'product_name_snapshot' => (string) $firstProduct->name,
                'variant_name_snapshot' => $firstVariant?->variant_name,
                'created_by' => (int) auth()->id(),
            ]);

            // بروزرسانی شماره سند
            $doc->update([
                'document_number' => 'PD-' . now()->format('Ymd') . '-' . str_pad((string) $doc->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($data['items'] as $index => $itemData) {
                $product = Product::query()->whereKey((int) $itemData['product_id'])->lockForUpdate()->firstOrFail();
                $variant = null;
                $deactivationType = ProductDeactivationDocument::TYPE_PRODUCT;

                if (!empty($itemData['variant_id'])) {
                    $variant = ProductVariant::query()
                        ->whereKey((int) $itemData['variant_id'])
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$variant) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "items.{$index}.variant_id" => 'تنوع انتخاب‌شده با کالای همین ردیف مطابقت ندارد.',
                        ]);
                    }

                    $deactivationType = ProductDeactivationDocument::TYPE_VARIANT;
                    if ((bool) $variant->is_active) {
                        $variant->update(['is_active' => false]);
                    }
                } else {
                    if ((bool) $product->is_sellable) {
                        $product->update(['is_sellable' => false]);
                    }
                    $product->variants()->where('is_active', true)->update(['is_active' => false]);
                }

                $category = null;
                $subcategory = null;
                if ($product->category) {
                    if ($product->category->parent_id) {
                        $subcategory = $product->category;
                        $category = Category::query()->find($product->category->parent_id);
                    } else {
                        $category = $product->category;
                    }
                }

                ProductDeactivationDocumentItem::create([
                    'document_id' => $doc->id,
                    'category_id' => $category?->id,
                    'subcategory_id' => $subcategory?->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'deactivation_type' => $deactivationType,
                    'deactivation_status' => 'deactivated',
                    'category_name_snapshot' => $category?->name,
                    'subcategory_name_snapshot' => $subcategory?->name,
                    'product_name_snapshot' => (string) $product->name,
                    'variant_name_snapshot' => $variant?->variant_name,
                ]);
            }
        });

        // هدایت به صفحه لیست اسناد با پیغام موفقیت
        return redirect()
            ->route('product-deactivation-documents.index')
            ->with('success', 'سند غیرفعال‌سازی با موفقیت ثبت شد.');
    }

    public function show(ProductDeactivationDocument $productDeactivationDocument)
    {
        $productDeactivationDocument->load([
            'creator:id,name',
            'items.product:id,name,is_sellable',
            'items.variant:id,product_id,variant_name,is_active',
        ]);

        $typeLabels = ProductDeactivationDocument::typeLabels();

        return view('product-deactivation-documents.show', [
            'document' => $productDeactivationDocument,
            'typeLabels' => $typeLabels,
        ]);
    }
}