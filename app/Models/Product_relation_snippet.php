<?php

// Add/replace this method inside app/Models/Product.php

public function warehouseStocks()
{
    return $this->hasMany(\App\Models\WarehouseStock::class)
        ->whereNotNull('product_variant_id');
}
