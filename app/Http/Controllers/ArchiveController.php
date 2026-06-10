<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PreinvoiceOrder;

class ArchiveController extends Controller
{
    public function showPreinvoice(string $uuid)
    {
        $order = PreinvoiceOrder::query()
            ->with([
                'items.product:id,name',
                'items.variant:id,variant_name',
                'creator:id,name',
                'warehouseReviewer:id,name',
                'shippingMethod:id,name,price',
                'reviews.user:id,name',
                'activityLogs.user:id,name',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('archive.preinvoice-show', compact('order'));
    }

    public function showInvoice(string $uuid)
    {
        $invoice = Invoice::query()
            ->with([
                'items.product:id,name',
                'items.variant:id,variant_name',
                'payments.creator:id,name',
                'payments.cheque',
                'notes.user:id,name',
                'histories.actor:id,name',
                'activityLogs.user:id,name',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return view('archive.invoice-show', compact('invoice'));
    }
}
