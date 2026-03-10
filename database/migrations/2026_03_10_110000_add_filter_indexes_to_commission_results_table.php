<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('commission_results', function (Blueprint $table) {
            $table->index(['commission_period_id', 'user_id'], 'commission_results_period_user_index');
            $table->index(['commission_period_id', 'category_id'], 'commission_results_period_category_index');
        });
    }

    public function down(): void
    {
        Schema::table('commission_results', function (Blueprint $table) {
            $table->dropIndex('commission_results_period_user_index');
            $table->dropIndex('commission_results_period_category_index');
        });
    }
};
