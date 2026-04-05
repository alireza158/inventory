<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockCountDocument;
use App\Models\Warehouse;
use App\Services\StockCountDocumentService;
use Illuminate\Http\Request;

class StocktakeController extends Controller
{
    public function __construct(private readonly StockCountDocumentService $service)
    {
    }

    public function index(Request $request)
    {
        $query = StockCountDocument::query()
            ->with(['warehouse', 'creator'])
            ->withCount('items')
            ->latest('id');

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', (int) $request->input('warehouse_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('document_number')) {
            $query->where('document_number', 'like', '%' . trim((string) $request->input('document_number')) . '%');
        }

        if ($request->filled('document_date')) {
            $query->whereDate('document_date', $request->input('document_date'));
        }

        $documents = $query->paginate(15)->withQueryString();
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson()) {
            return response()->json($documents);
        }

        return view('stocktake.index', compact('documents', 'warehouses'));
    }

    public function create()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('stocktake.form', [
            'mode' => 'create',
            'document' => null,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedPayload($request);
        $document = $this->service->create($data, (int) auth()->id());

        if ($request->expectsJson()) {
            return response()->json($document, 201);
        }

        return redirect()->route('stock-count-documents.edit', $document)->with('success', 'سند انبارگردانی با موفقیت ایجاد شد.');
    }

    public function show(Request $request, StockCountDocument $stockCountDocument)
    {
        $stockCountDocument->load(['warehouse', 'items.product', 'creator', 'updater', 'finalizer', 'history.doer']);

        if ($request->expectsJson()) {
            return response()->json($stockCountDocument);
        }

        return view('stocktake.show', ['document' => $stockCountDocument]);
    }

    public function view(Request $request, StockCountDocument $stockCountDocument)
    {
        $stockCountDocument->load(['warehouse', 'items.product', 'creator', 'updater', 'finalizer', 'history.doer']);

        if ($request->expectsJson()) {
            return response()->json($stockCountDocument);
        }

        return view('stocktake.show', ['document' => $stockCountDocument]);
    }

    public function edit(StockCountDocument $stockCountDocument)
    {
        $stockCountDocument->load(['warehouse', 'items.product']);
        $warehouses = Warehouse::query()->where('is_active', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return view('stocktake.form', [
            'mode' => 'edit',
            'document' => $stockCountDocument,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(Request $request, StockCountDocument $stockCountDocument)
    {
        $data = $this->validatedPayload($request);
        $updated = $this->service->update($stockCountDocument, $data, (int) auth()->id());

        if ($request->expectsJson()) {
            return response()->json($updated);
        }

        return redirect()->route('stock-count-documents.edit', $updated)->with('success', 'سند انبارگردانی به‌روزرسانی شد.');
    }

    public function finalize(Request $request, StockCountDocument $stockCountDocument)
    {
        $finalized = $this->service->finalize($stockCountDocument, (int) auth()->id());

        if ($request->expectsJson()) {
            return response()->json($finalized);
        }

        return redirect()->route('stock-count-documents.view', $finalized)->with('success', 'سند انبارگردانی نهایی شد.');
    }

    public function cancel(Request $request, StockCountDocument $stockCountDocument)
    {
        $cancelled = $this->service->cancel($stockCountDocument, (int) auth()->id());

        if ($request->expectsJson()) {
            return response()->json($cancelled);
        }

        return back()->with('success', 'سند انبارگردانی لغو شد.');
    }

    public function systemQuantity(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        return response()->json([
            'system_quantity' => $this->service->getSystemQuantity((int) $data['warehouse_id'], (int) $data['product_id']),
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'document_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.actual_quantity' => ['required', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ], [
            'items.min' => 'حداقل یک ردیف کالا در سند لازم است.',
            'items.*.product_id.distinct' => 'تکرار کالا در یک سند مجاز نیست.',
        ]);
    }
}
