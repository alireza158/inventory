<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_count_document_items', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_count_document_items', 'product_variant_id')) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->restrictOnDelete();
            }
        });

        Schema::table('stock_count_document_items', function (Blueprint $table) {
            if ($this->hasIndex('stock_count_document_items', 'stock_count_document_items_document_id_product_id_unique')) {
                $table->dropUnique('stock_count_document_items_document_id_product_id_unique');
            }

            if (!$this->hasIndex('stock_count_document_items', 'stock_count_document_items_doc_product_variant_unique')) {
                $table->unique(['document_id', 'product_id', 'product_variant_id'], 'stock_count_document_items_doc_product_variant_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_count_document_items', function (Blueprint $table) {
            if ($this->hasIndex('stock_count_document_items', 'stock_count_document_items_doc_product_variant_unique')) {
                $table->dropUnique('stock_count_document_items_doc_product_variant_unique');
            }

            if (Schema::hasColumn('stock_count_document_items', 'product_variant_id')) {
                $table->dropConstrainedForeignId('product_variant_id');
            }

            if (!$this->hasIndex('stock_count_document_items', 'stock_count_document_items_document_id_product_id_unique')) {
                $table->unique(['document_id', 'product_id']);
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        return (bool) $exists;
    }
};
