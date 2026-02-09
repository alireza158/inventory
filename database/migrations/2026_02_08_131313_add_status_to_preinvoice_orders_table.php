<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('preinvoice_orders', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('created_by');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('preinvoice_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
