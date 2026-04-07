<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductDeactivationDocument;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductDeactivationDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductDeactivationDocument::query()
            ->with([
                'product:id,name,is_sellable',
                'variant:id,product_id,variant_name,is_active',
                'creator:id,name',
            ]);

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('deactivation_type')) {
            $query->where('deactivation_type', $request->deactivation_type);
        }

        if ($request->filled('reason_type')) {
            $query->where('reason_type', $request->reason_type);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', (int) $request->created_by);
        }

        if ($request->filled('product_name')) {
            $q = trim((string) $request->product_name);
            $query->where(function ($qq) use ($q) {
                $qq->where('product_name_snapshot', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($p) use ($q) {
                        $p->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('variant_name')) {
            $q = trim((string) $request->variant_name);
            $query->where(function ($qq) use ($q) {
                $qq->where('variant_name_snapshot', 'like', "%{$q}%")
                    ->orWhereHas('variant', function ($v) use ($q) {
                        $v->where('variant_name', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('current_status')) {
            $status = (string) $request->current_status;

            $query->where(function ($qq) use ($status) {
                $qq->where(function ($q) use ($status) {
                    $q->where('deactivation_type', ProductDeactivationDocument::TYPE_PRODUCT)
                        ->whereHas('product', function ($p) use ($status) {
                            $p->where('is_sellable', $status === 'active');
                        });
                })->orWhere(function ($q) use ($status) {
                    $q->where('deactivation_type', ProductDeactivationDocument::TYPE_VARIANT)
                        ->whereHas('variant', function ($v) use ($status) {
                            $v->where('is_active', $status === 'active');
                        });
                });
            });
        }

        $documents = $query->latest('id')->paginate(20)->withQueryString();

        $typeLabels = ProductDeactivationDocument::typeLabels();
        $reasonLabels = ProductDeactivationDocument::reasonLabels();
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('product-deactivation-documents.index', compact(
            'documents',
            'typeLabels',
            'reasonLabels',
            'users'
        ));
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
                'variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('variant_name');
                }
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'is_sellable']);

        $reasonLabels = ProductDeactivationDocument::reasonLabels();
        $typeLabels = ProductDeactivationDocument::typeLabels();

        return view('product-deactivation-documents.create', compact(
            'products',
            'reasonLabels',
            'typeLabels'
        ));
    }

    public function store(Request $request)
    {
        $reasonKeys = array_keys(ProductDeactivationDocument::reasonLabels());

        $data = $request->validate([
            'deactivation_type' => [
                'required',
                Rule::in([
                    ProductDeactivationDocument::TYPE_PRODUCT,
                    ProductDeactivationDocument::TYPE_VARIANT,
                ]),
            ],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'reason_type' => ['required', Rule::in($reasonKeys)],
            'reason_text' => ['required', 'string', 'min:3', 'max:2000'],
            'description' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($data) {
            $product = Product::query()
                ->whereKey((int) $data['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $variant = null;

            if (($data['deactivation_type'] ?? null) === ProductDeactivationDocument::TYPE_VARIANT) {
                if (empty($data['variant_id'])) {
                    abort(422, 'برای غیرفعال‌سازی تنوع، انتخاب تنوع الزامی است.');
                }

                $variant = ProductVariant::query()
                    ->whereKey((int) $data['variant_id'])
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    abort(422, 'تنوع انتخاب‌شده متعلق به همین محصول نیست.');
                }

                if (!(bool) $variant->is_active) {
                    abort(422, 'این تنوع از قبل غیرفعال است.');
                }
            }

            if (($data['deactivation_type'] ?? null) === ProductDeactivationDocument::TYPE_PRODUCT) {
                if (!(bool) $product->is_sellable) {
                    abort(422, 'این محصول از قبل غیرفعال است.');
                }
            }

            $doc = ProductDeactivationDocument::create([
                'document_number' => 'TMP-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'deactivation_type' => $data['deactivation_type'],
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'reason_type' => $data['reason_type'],
                'reason_text' => trim((string) $data['reason_text']),
                'description' => !empty($data['description']) ? trim((string) $data['description']) : null,
                'product_name_snapshot' => (string) $product->name,
                'variant_name_snapshot' => $variant?->variant_name,
                'created_by' => (int) auth()->id(),
            ]);

            $doc->update([
                'document_number' => 'PD-' . now()->format('Ymd') . '-' . str_pad((string) $doc->id, 6, '0', STR_PAD_LEFT),
            ]);

            if ($data['deactivation_type'] === ProductDeactivationDocument::TYPE_PRODUCT) {
                $product->update(['is_sellable' => false]);
                $product->variants()->update(['is_active' => false]);
            } else {
                $variant->update(['is_active' => false]);
            }
        });

        return redirect()
            ->route('product-deactivation-documents.index')
            ->with('success', 'سند غیرفعال‌سازی با موفقیت ثبت شد.');
    }

    public function show(ProductDeactivationDocument $productDeactivationDocument)
    {
        $productDeactivationDocument->load([
            'product:id,name,is_sellable',
            'variant:id,product_id,variant_name,is_active',
            'creator:id,name',
        ]);

        $typeLabels = ProductDeactivationDocument::typeLabels();
        $reasonLabels = ProductDeactivationDocument::reasonLabels();

        return view('product-deactivation-documents.show', [
            'document' => $productDeactivationDocument,
            'typeLabels' => $typeLabels,
            'reasonLabels' => $reasonLabels,
        ]);
    }
}