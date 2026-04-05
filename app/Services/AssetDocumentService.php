<?php

namespace App\Services;

use App\Models\AssetDocument;
use App\Models\AssetDocumentItem;
use App\Models\AssetDocumentItemCode;
use Illuminate\Support\Facades\DB;

class AssetDocumentService
{
    public function __construct(
        private readonly AssetDocumentValidationService $validationService,
        private readonly AssetDocumentHistoryService $historyService,
    ) {}

    public function generateDocumentNumber(): string
    {
        $prefix = 'AD-' . now()->format('Ymd');
        $latestToday = AssetDocument::query()->where('document_number', 'like', $prefix . '-%')->count();
        return $prefix . '-' . str_pad((string) ($latestToday + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $header, array $items, ?int $userId = null): AssetDocument
    {
        $normalizedItems = $this->validationService->normalizeAndValidateItems($items);

        return DB::transaction(function () use ($header, $normalizedItems, $userId) {
            $document = AssetDocument::query()->create([
                'document_number' => $header['document_number'] ?? $this->generateDocumentNumber(),
                'document_date' => $header['document_date'],
                'personnel_id' => $header['personnel_id'],
                'status' => AssetDocument::STATUS_DRAFT,
                'description' => $header['description'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($normalizedItems as $row) {
                $item = AssetDocumentItem::query()->create([
                    'document_id' => $document->id,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'description' => $row['description'],
                ]);

                foreach ($row['codes'] as $code) {
                    AssetDocumentItemCode::query()->create([
                        'document_item_id' => $item->id,
                        'asset_code' => $code,
                    ]);
                }
            }

            $this->historyService->log($document, 'created', null, ['status' => AssetDocument::STATUS_DRAFT], 'ایجاد سند اموال', $userId);

            return $document->fresh(['personnel', 'items.codes']);
        });
    }

    public function update(AssetDocument $document, array $header, array $items, ?int $userId = null): AssetDocument
    {
        if ($document->status !== AssetDocument::STATUS_DRAFT) {
            abort(422, 'فقط سند پیش‌نویس قابل ویرایش است.');
        }

        $normalizedItems = $this->validationService->normalizeAndValidateItems($items);

        return DB::transaction(function () use ($document, $header, $normalizedItems, $userId) {
            $old = $document->toArray();

            $document->update([
                'document_date' => $header['document_date'],
                'personnel_id' => $header['personnel_id'],
                'description' => $header['description'] ?? null,
                'updated_by' => $userId,
            ]);

            $document->items()->delete();

            foreach ($normalizedItems as $row) {
                $item = AssetDocumentItem::query()->create([
                    'document_id' => $document->id,
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'description' => $row['description'],
                ]);

                foreach ($row['codes'] as $code) {
                    AssetDocumentItemCode::query()->create([
                        'document_item_id' => $item->id,
                        'asset_code' => $code,
                    ]);
                }
            }

            $this->historyService->log($document, 'updated', $old, $document->fresh()->toArray(), 'ویرایش سند اموال', $userId);

            return $document->fresh(['personnel', 'items.codes']);
        });
    }

    public function finalize(AssetDocument $document, ?int $userId = null): AssetDocument
    {
        if ($document->status !== AssetDocument::STATUS_DRAFT) {
            abort(422, 'فقط سند پیش‌نویس قابل نهایی‌سازی است.');
        }

        $document->update(['status' => AssetDocument::STATUS_FINALIZED, 'updated_by' => $userId]);
        $this->historyService->log($document, 'finalized', ['status' => AssetDocument::STATUS_DRAFT], ['status' => AssetDocument::STATUS_FINALIZED], 'نهایی‌سازی سند', $userId);

        return $document->fresh();
    }

    public function cancel(AssetDocument $document, ?int $userId = null): AssetDocument
    {
        if ($document->status === AssetDocument::STATUS_CANCELLED) {
            return $document;
        }

        $old = $document->status;
        $document->update(['status' => AssetDocument::STATUS_CANCELLED, 'updated_by' => $userId]);
        $this->historyService->log($document, 'cancelled', ['status' => $old], ['status' => AssetDocument::STATUS_CANCELLED], 'لغو سند اموال', $userId);

        return $document->fresh();
    }
}
