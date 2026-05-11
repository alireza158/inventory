<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\InventoryWebhookService;

class ProductInventoryObserver
{
    public function updated(Product $product): void
    {
        if (!$product->wasChanged(['price', 'stock', 'reserved'])) {
            return;
        }

        InventoryWebhookService::send('product.updated', [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->stock,
            'reserved' => $product->reserved,
            'changed' => $product->getChanges(),
        ]);
    }
}
