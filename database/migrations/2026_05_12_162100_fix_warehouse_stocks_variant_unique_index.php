<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // حذف unique قدیمی warehouse_id + product_id
        $oldIndexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouse_stocks'
              AND index_name = 'warehouse_stocks_warehouse_id_product_id_unique'
        ");

        if ((int) ($oldIndexExists->cnt ?? 0) > 0) {
            DB::statement("
                ALTER TABLE warehouse_stocks
                DROP INDEX warehouse_stocks_warehouse_id_product_id_unique
            ");
        }

        // اگر ستون product_variant_id هنوز وجود ندارد، اضافه شود
        if (!Schema::hasColumn('warehouse_stocks', 'product_variant_id')) {
            Schema::table('warehouse_stocks', function (Blueprint $table) {
                $table->foreignId('product_variant_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('product_variants')
                    ->nullOnDelete();
            });
        }

        // اضافه کردن unique درست: warehouse_id + product_variant_id
        $newIndexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouse_stocks'
              AND index_name = 'warehouse_stocks_warehouse_variant_unique'
        ");

        if ((int) ($newIndexExists->cnt ?? 0) === 0) {
            DB::statement("
                ALTER TABLE warehouse_stocks
                ADD UNIQUE INDEX warehouse_stocks_warehouse_variant_unique
                (warehouse_id, product_variant_id)
            ");
        }

        // ایندکس معمولی برای سرعت جستجو
        $normalIndexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouse_stocks'
              AND index_name = 'warehouse_stocks_wh_product_variant_index'
        ");

        if ((int) ($normalIndexExists->cnt ?? 0) === 0) {
            DB::statement("
                ALTER TABLE warehouse_stocks
                ADD INDEX warehouse_stocks_wh_product_variant_index
                (warehouse_id, product_id, product_variant_id)
            ");
        }
    }

    public function down(): void
    {
        $normalIndexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouse_stocks'
              AND index_name = 'warehouse_stocks_wh_product_variant_index'
        ");

        if ((int) ($normalIndexExists->cnt ?? 0) > 0) {
            DB::statement("
                ALTER TABLE warehouse_stocks
                DROP INDEX warehouse_stocks_wh_product_variant_index
            ");
        }

        $newIndexExists = DB::selectOne("
            SELECT COUNT(1) AS cnt
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'warehouse_stocks'
              AND index_name = 'warehouse_stocks_warehouse_variant_unique'
        ");

        if ((int) ($newIndexExists->cnt ?? 0) > 0) {
            DB::statement("
                ALTER TABLE warehouse_stocks
                DROP INDEX warehouse_stocks_warehouse_variant_unique
            ");
        }
    }
};