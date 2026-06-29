<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PreinvoiceOrder;
use App\Services\SalesPrintDocumentService;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function showPreinvoice(string $uuid, Request $request, SalesPrintDocumentService $printService)
    {
        $order = PreinvoiceOrder::query()
            ->with([
                'items.product',
                'items.variant.modelList',
                'items.variant.color',
                'creator:id,name',
                'warehouseReviewer:id,name',
                'shippingMethod:id,name,price',
                'reviews.user:id,name',
                'activityLogs.user:id,name',
                'invoice:id,uuid,preinvoice_order_id,status,created_at,document_date',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($request->has('print') || $request->has('mode')) {
            $printData = $printService->preinvoiceData($order, (string) $request->query('mode', $request->query('print', 'warehouse')));

            return view('prints.invoice', compact('printData'));
        }

        return view('archive.preinvoice-show', compact('order'));
    }

    public function showInvoice(string $uuid, Request $request, SalesPrintDocumentService $printService)
    {
        $invoice = Invoice::query()
            ->with([
                'items.product',
                'items.variant.modelList',
                'items.variant.color',
                'payments.creator:id,name',
                'payments.cheque',
                'notes.user:id,name',
                'histories.actor:id,name',
                'activityLogs.user:id,name',
                'invoice:id,uuid,preinvoice_order_id,status,created_at,document_date',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();

        if ($request->has('print') || $request->has('mode')) {
            $printData = $printService->invoiceData($invoice, (string) $request->query('mode', $request->query('print', 'warehouse')));

            return view('prints.invoice', compact('printData'));
        }

        return view('archive.invoice-show', compact('invoice'));
    }
}
