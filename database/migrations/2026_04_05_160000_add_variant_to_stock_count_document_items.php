<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_count_document_items', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->dropUnique('stock_count_document_items_document_id_product_id_unique');
            $table->unique(['document_id', 'product_id', 'product_variant_id'], 'stock_count_document_items_doc_product_variant_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_count_document_items', function (Blueprint $table) {
            $table->dropUnique('stock_count_document_items_doc_product_variant_unique');
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['document_id', 'product_id']);
        });
    }
};
