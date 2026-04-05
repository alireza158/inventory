<?php

namespace App\Http\Controllers;

use App\Models\AssetDocument;
use App\Models\AssetDocumentItemCode;
use App\Models\AssetPersonnel;
use App\Services\AssetCodeService;
use App\Services\AssetDocumentService;
use Illuminate\Http\Request;

class AssetDocumentController extends Controller
{
    public function __construct(
        private readonly AssetDocumentService $service,
        private readonly AssetCodeService $codeService,
    ) {}

    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $personnelId = (int) $request->query('personnel_id', 0);
        $documentNo = trim((string) $request->query('document_number', ''));

        $documents = AssetDocument::query()
            ->with('personnel')
            ->withCount('items')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($personnelId > 0, fn ($q) => $q->where('personnel_id', $personnelId))
            ->when($documentNo !== '', fn ($q) => $q->where('document_number', 'like', "%{$documentNo}%"))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $personnel = AssetPersonnel::query()->where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']);
        $statusLabels = AssetDocument::statusLabels();

        return view('asset.documents.index', compact('documents', 'personnel', 'status', 'personnelId', 'documentNo', 'statusLabels'));
    }

    public function create()
    {
        $personnel = AssetPersonnel::query()->where('is_active', true)->orderBy('full_name')->get();

        return view('asset.documents.form', [
            'document' => new AssetDocument([
                'document_date' => now()->toDateString(),
                'status' => AssetDocument::STATUS_DRAFT,
            ]),
            'personnel' => $personnel,
            'statusLabels' => AssetDocument::statusLabels(),
            'action' => route('asset.documents.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_date' => 'required|date',
            'personnel_id' => 'required|exists:asset_personnel,id',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.asset_codes_input' => 'required|string',
            'items.*.description' => 'nullable|string',
        ]);

        $document = $this->service->create($data, $data['items'], auth()->id());

        return redirect()->route('asset.documents.show', $document)->with('success', 'سند اموال با موفقیت ثبت شد.');
    }

    public function show(AssetDocument $document)
    {
        $document->load(['personnel', 'items.codes', 'creator', 'updater', 'histories.actor']);
        $statusLabels = AssetDocument::statusLabels();

        return view('asset.documents.show', compact('document', 'statusLabels'));
    }

    public function view(AssetDocument $document)
    {
        $document->load(['personnel', 'items.codes']);
        $statusLabels = AssetDocument::statusLabels();

        return response()->json([
            'document_number' => $document->document_number,
            'document_date' => optional($document->document_date)->toDateString(),
            'status' => $document->status,
            'status_label' => $statusLabels[$document->status] ?? $document->status,
            'personnel' => $document->personnel,
            'items' => $document->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'quantity' => (int) $item->quantity,
                'codes' => $item->codes->pluck('asset_code')->values(),
            ]),
        ]);
    }

    public function edit(AssetDocument $document)
    {
        if ($document->status !== AssetDocument::STATUS_DRAFT) {
            return redirect()->route('asset.documents.show', $document)->withErrors(['status' => 'فقط سند پیش‌نویس قابل ویرایش است.']);
        }

        $document->load('items.codes');
        $personnel = AssetPersonnel::query()->where('is_active', true)->orderBy('full_name')->get();

        return view('asset.documents.form', [
            'document' => $document,
            'personnel' => $personnel,
            'statusLabels' => AssetDocument::statusLabels(),
            'action' => route('asset.documents.update', $document),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, AssetDocument $document)
    {
        $data = $request->validate([
            'document_date' => 'required|date',
            'personnel_id' => 'required|exists:asset_personnel,id',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.asset_codes_input' => 'required|string',
            'items.*.description' => 'nullable|string',
        ]);

        $updated = $this->service->update($document, $data, $data['items'], auth()->id());

        return redirect()->route('asset.documents.show', $updated)->with('success', 'سند اموال بروزرسانی شد.');
    }

    public function finalize(AssetDocument $document)
    {
        $document = $this->service->finalize($document, auth()->id());

        return back()->with('success', 'سند اموال نهایی شد.');
    }

    public function cancel(AssetDocument $document)
    {
        $document = $this->service->cancel($document, auth()->id());

        return back()->with('success', 'سند اموال لغو شد.');
    }

    public function codeSearchPage(Request $request)
    {
        $code = trim((string) $request->query('code', ''));
        $result = null;

        if ($code !== '') {
            $result = AssetDocumentItemCode::query()
                ->where('asset_code', $code)
                ->with(['item.document.personnel'])
                ->first();
        }

        return view('asset.codes.search', compact('code', 'result'));
    }

    public function findByCode(string $code)
    {
        if (!preg_match('/^\d{4}$/', $code)) {
            abort(422, 'کد اموال باید دقیقاً 4 رقم باشد.');
        }

        $result = AssetDocumentItemCode::query()
            ->where('asset_code', $code)
            ->with(['item.document.personnel'])
            ->firstOrFail();

        return response()->json([
            'asset_code' => $result->asset_code,
            'item_name' => $result->item?->item_name,
            'document_number' => $result->item?->document?->document_number,
            'document_status' => $result->item?->document?->status,
            'personnel' => $result->item?->document?->personnel?->full_name,
        ]);
    }
}
