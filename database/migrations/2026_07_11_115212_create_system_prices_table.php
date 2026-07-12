<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();

            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('amount_in_cents');

            $table->unsignedBigInteger('compare_at_amount_in_cents')->nullable();

            $table->timestamps();

            $table->unique(['system_id', 'price_list_id'], 'system_prices_system_list_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_prices');
    }
};
