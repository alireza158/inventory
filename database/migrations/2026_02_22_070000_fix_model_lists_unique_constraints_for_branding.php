<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('model_lists')) {
            return;
        }

        $indexes = collect(DB::select("SHOW INDEX FROM model_lists"))->pluck('Key_name')->unique()->values()->all();

        if (in_array('model_lists_model_name_unique', $indexes, true)) {
            Schema::table('model_lists', function (Blueprint $table) {
                $table->dropUnique('model_lists_model_name_unique');
            });
        }

        $indexes = collect(DB::select("SHOW INDEX FROM model_lists"))->pluck('Key_name')->unique()->values()->all();
        if (in_array('model_lists_brand_model_name_unique', $indexes, true)) {
            Schema::table('model_lists', function (Blueprint $table) {
                $table->dropUnique('model_lists_brand_model_name_unique');
            });
        }

        if (Schema::hasColumn('model_lists', 'brand')) {
            Schema::table('model_lists', function (Blueprint $table) {
                $table->unique(['brand', 'model_name'], 'model_lists_brand_model_name_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('model_lists')) {
            return;
        }

        $indexes = collect(DB::select("SHOW INDEX FROM model_lists"))->pluck('Key_name')->unique()->values()->all();
        if (in_array('model_lists_brand_model_name_unique', $indexes, true)) {
            Schema::table('model_lists', function (Blueprint $table) {
                $table->dropUnique('model_lists_brand_model_name_unique');
            });
        }

        $indexes = collect(DB::select("SHOW INDEX FROM model_lists"))->pluck('Key_name')->unique()->values()->all();
        if (!in_array('model_lists_model_name_unique', $indexes, true)) {
            Schema::table('model_lists', function (Blueprint $table) {
                $table->unique('model_name', 'model_lists_model_name_unique');
            });
        }
    }
};
