<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['invoice_items', 'preinvoice_order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'product_name_snapshot')) {
                    $table->string('product_name_snapshot')->nullable()->after('product_id');
                }
                if (! Schema::hasColumn($tableName, 'variant_name_snapshot')) {
                    $table->string('variant_name_snapshot')->nullable()->after('variant_id');
                }
                if (! Schema::hasColumn($tableName, 'variant_code_snapshot')) {
                    $table->string('variant_code_snapshot')->nullable()->after('variant_name_snapshot');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['invoice_items', 'preinvoice_order_items'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['product_name_snapshot', 'variant_name_snapshot', 'variant_code_snapshot']);
            });
        }
    }
};
