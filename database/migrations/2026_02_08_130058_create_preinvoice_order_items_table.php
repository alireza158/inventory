<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('preinvoice_order_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('preinvoice_order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id'); // product_variants.id

            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('price')->default(0); // price per unit at time of draft

            $table->timestamps();

            $table->foreign('preinvoice_order_id')
                ->references('id')->on('preinvoice_orders')
                ->onDelete('cascade');

            $table->index(['product_id']);
            $table->index(['variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preinvoice_order_items');
    }
};
