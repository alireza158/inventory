<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceNoteController extends Controller
{
    public function store(string $uuid, Request $request)
    {
        $invoice = \App\Models\Invoice::where('uuid',$uuid)->firstOrFail();

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $invoice->notes()->create([
            'user_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        return back()->with('success','✅ یادداشت ثبت شد.');
    }
}
