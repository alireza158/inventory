<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('commission_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sold_amount')->default(0);
            $table->unsignedInteger('sold_qty')->default(0);
            $table->unsignedBigInteger('target_amount')->default(0);
            $table->unsignedInteger('target_qty')->nullable();
            $table->decimal('achievement_percent', 8, 2)->default(0);
            $table->string('commission_type');
            $table->decimal('commission_value', 12, 2)->default(0);
            $table->unsignedBigInteger('commission_amount')->default(0);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['commission_period_id', 'user_id', 'category_id'], 'commission_results_period_user_category_unique');
            $table->index(['commission_period_id', 'calculated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_results');
    }
};
