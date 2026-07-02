<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'warehouse_received_at')) {
                $table->timestamp('warehouse_received_at')->nullable()->after('status_changed_by');
            }
            if (! Schema::hasColumn('invoices', 'warehouse_received_by')) {
                $table->foreignId('warehouse_received_by')->nullable()->after('warehouse_received_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'collection_started_at')) {
                $table->timestamp('collection_started_at')->nullable()->after('warehouse_received_by');
            }
            if (! Schema::hasColumn('invoices', 'collection_started_by')) {
                $table->foreignId('collection_started_by')->nullable()->after('collection_started_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'collection_completed_at')) {
                $table->timestamp('collection_completed_at')->nullable()->after('collection_started_by');
            }
            if (! Schema::hasColumn('invoices', 'collection_completed_by')) {
                $table->foreignId('collection_completed_by')->nullable()->after('collection_completed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('invoices', 'shipping_status')) {
                $table->string('shipping_status')->nullable()->default('pending')->after('collection_completed_by');
            }
            if (! Schema::hasColumn('invoices', 'shipping_note')) {
                $table->text('shipping_note')->nullable()->after('shipping_status');
            }
            if (! Schema::hasColumn('invoices', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable()->after('shipping_note');
            }
            if (! Schema::hasColumn('invoices', 'shipped_by')) {
                $table->foreignId('shipped_by')->nullable()->after('shipped_at')->constrained('users')->nullOnDelete();
            }
        });
    }
};
