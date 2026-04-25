<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unique('short_barcode', 'products_short_barcode_unique');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique('barcode', 'product_variants_barcode_unique');
            $table->unique('sku', 'product_variants_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_barcode_unique');
            $table->dropUnique('product_variants_sku_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_short_barcode_unique');
        });
    }
};
