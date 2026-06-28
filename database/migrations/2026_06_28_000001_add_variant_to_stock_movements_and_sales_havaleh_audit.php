<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'product_variant_id')) {
                $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            }
        });

        Schema::table('sales_havaleh_histories', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_havaleh_histories', 'invoice_uuid')) {
                $table->string('invoice_uuid')->nullable()->after('invoice_id');
            }
            if (! Schema::hasColumn('sales_havaleh_histories', 'invoice_item_id')) {
                $table->unsignedBigInteger('invoice_item_id')->nullable()->after('invoice_uuid');
            }
            if (! Schema::hasColumn('sales_havaleh_histories', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('invoice_item_id');
            }
            if (! Schema::hasColumn('sales_havaleh_histories', 'variant_id')) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            }
            foreach (['old_quantity','new_quantity','delta','returned_to_stock_quantity','consumed_from_stock_quantity'] as $column) {
                if (! Schema::hasColumn('sales_havaleh_histories', $column)) {
                    $table->integer($column)->nullable()->after('variant_id');
                }
            }
            if (! Schema::hasColumn('sales_havaleh_histories', 'reason')) {
                $table->string('reason', 100)->nullable()->after('description');
            }
            if (! Schema::hasColumn('sales_havaleh_histories', 'note')) {
                $table->text('note')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_havaleh_histories', function (Blueprint $table) {
            $table->dropColumn(['invoice_uuid','invoice_item_id','product_id','variant_id','old_quantity','new_quantity','delta','returned_to_stock_quantity','consumed_from_stock_quantity','reason','note']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'product_variant_id')) {
                $table->dropConstrainedForeignId('product_variant_id');
            }
        });
    }
};
