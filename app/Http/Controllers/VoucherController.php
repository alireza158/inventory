<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string)$request->get('q', ''));

        $vouchers = StockMovement::with(['product','user'])
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('product', function ($p) use ($q) {
                    $p->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
                })->orWhere('reference', 'like', "%{$q}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('vouchers.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required','exists:products,id'],
            'type' => ['required','in:in,out'],
            'reason' => ['required','in:purchase,sale,return,transfer,adjustment'],
            'quantity' => ['required','integer','min:1'],
            'reference' => ['nullable','string','max:100'], // شماره حواله
            'note' => ['nullable','string','max:255'],
        ]);

        DB::transaction(function () use ($data) {
            $p = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();

            $before = (int)$p->stock;
            $qty = (int)$data['quantity'];

            if ($data['type'] === 'out' && $before < $qty) {
                abort(422, 'موجودی کافی نیست.');
            }

            $after = $data['type'] === 'in' ? $before + $qty : $before - $qty;

            StockMovement::create([
                'product_id' => $p->id,
                'user_id' => auth()->id(),
                'type' => $data['type'],
                'reason' => $data['reason'],
                'quantity' => $qty,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            $p->update(['stock' => $after]);
        });

        return redirect()->route('vouchers.index')->with('success', 'حواله ثبت شد و موجودی بروزرسانی شد.');
    }
}
