<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preinvoice_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('preinvoice_order_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('price');
            }
            if (! Schema::hasColumn('preinvoice_order_items', 'line_discount_amount')) {
                $table->unsignedBigInteger('line_discount_amount')->default(0)->after('sort_order');
            }
        });

        DB::statement('SET @rn := 0, @oid := 0');
        DB::statement('UPDATE preinvoice_order_items p JOIN (SELECT id, (@rn := IF(@oid = preinvoice_order_id, @rn + 1, 1)) AS rn, (@oid := preinvoice_order_id) FROM preinvoice_order_items ORDER BY preinvoice_order_id, id) x ON x.id = p.id SET p.sort_order = IF(p.sort_order = 0, x.rn, p.sort_order)');

        Schema::table('preinvoice_order_items', function (Blueprint $table) {
            $table->index(['preinvoice_order_id', 'sort_order'], 'preinvoice_items_order_idx');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('line_total');
            }
            if (! Schema::hasColumn('invoice_items', 'line_discount_amount')) {
                $table->unsignedBigInteger('line_discount_amount')->default(0)->after('sort_order');
            }
        });

        DB::statement('SET @rn := 0, @iid := 0');
        DB::statement('UPDATE invoice_items i JOIN (SELECT id, (@rn := IF(@iid = invoice_id, @rn + 1, 1)) AS rn, (@iid := invoice_id) FROM invoice_items ORDER BY invoice_id, id) x ON x.id = i.id SET i.sort_order = IF(i.sort_order = 0, x.rn, i.sort_order)');

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['invoice_id', 'sort_order'], 'invoice_items_order_idx');
        });

        $duplicatePreinvoices = DB::table('invoices')
            ->whereNotNull('preinvoice_order_id')
            ->select('preinvoice_order_id', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('preinvoice_order_id')
            ->having('aggregate', '>', 1)
            ->exists();

        if (! $duplicatePreinvoices) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unique('preinvoice_order_id', 'invoices_preinvoice_order_id_unique');
            });
        }

        if (! Schema::hasTable('invoice_edit_audits')) {
            Schema::create('invoice_edit_audits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('reason');
                $table->json('changes_before')->nullable();
                $table->json('changes_after')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_edit_audits');
    }
};
