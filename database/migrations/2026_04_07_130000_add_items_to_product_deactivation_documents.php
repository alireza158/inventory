<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product_deactivation_documents', function (Blueprint $table) {
            $table->unsignedInteger('items_count')->default(1)->after('variant_id');
        });

        Schema::create('product_deactivation_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('product_deactivation_documents')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->enum('deactivation_type', ['product', 'variant']);
            $table->enum('deactivation_status', ['deactivated'])->default('deactivated');
            $table->string('category_name_snapshot')->nullable();
            $table->string('subcategory_name_snapshot')->nullable();
            $table->string('product_name_snapshot');
            $table->string('variant_name_snapshot')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'deactivation_type']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_deactivation_document_items');

        Schema::table('product_deactivation_documents', function (Blueprint $table) {
            $table->dropColumn('items_count');
        });
    }
};
