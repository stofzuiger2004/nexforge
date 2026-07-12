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
        Schema::create('variant_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();

            $table->unsignedBigInteger('amount_in_cents');

            $table->unsignedBigInteger('compare_at_amount_in_cents')->nullable();

            $table->timestamps();

            $table->unique(['price_list_id', 'product_variant_id'], 'variant_prices_list_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_prices');
    }
};
