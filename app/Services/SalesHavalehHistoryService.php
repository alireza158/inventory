<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\SalesHavalehHistory;

class SalesHavalehHistoryService
{
    public function log(
        Invoice $invoice,
        string $actionType,
        ?string $fieldName,
        mixed $oldValue,
        mixed $newValue,
        ?string $description = null,
        ?int $doneBy = null,
        array $extra = []
    ): SalesHavalehHistory {
        return SalesHavalehHistory::query()->create(array_merge([
            'invoice_id' => $invoice->id,
            'invoice_uuid' => $invoice->uuid,
            'action_type' => $actionType,
            'field_name' => $fieldName,
            'old_value' => $oldValue !== null ? (string) $oldValue : null,
            'new_value' => $newValue !== null ? (string) $newValue : null,
            'description' => $description,
            'done_by' => $doneBy,
            'done_at' => now(),
        ], $extra));
    }
}
