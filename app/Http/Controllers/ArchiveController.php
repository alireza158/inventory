<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PreinvoiceOrder;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    public function index(Request $request)
    {
        $type = (string) $request->query('type', 'all');

        $preinvoices = PreinvoiceOrder::query()
            ->with([
                'items.product:id,name',
                'items.variant:id,variant_name',
                'creator:id,name',
                'warehouseReviewer:id,name',
                'reviews.user:id,name',
            ])
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'preinvoice_page')
            ->withQueryString();

        $invoices = Invoice::query()
            ->with([
                'items.product:id,name',
                'items.variant:id,variant_name',
                'payments.creator:id,name',
                'payments.cheque',
                'notes.user:id,name',
                'histories.actor:id,name',
            ])
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'invoice_page')
            ->withQueryString();

        return view('archive.index', compact('preinvoices', 'invoices', 'type'));
    }
}
