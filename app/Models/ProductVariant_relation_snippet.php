<?php

// Add this method inside app/Models/ProductVariant.php

public function warehouseStocks()
{
    return $this->hasMany(\App\Models\WarehouseStock::class, 'product_variant_id');
}
