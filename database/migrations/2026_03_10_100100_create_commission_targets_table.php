<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('target_amount')->default(0);
            $table->unsignedInteger('target_qty')->nullable();
            $table->string('commission_type'); // percent|fixed (phase 1)
            $table->decimal('commission_value', 12, 2)->default(0);
            $table->decimal('min_percent_to_activate', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['commission_period_id', 'user_id', 'category_id'], 'commission_targets_period_user_category_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_targets');
    }
};
