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
        Schema::create('system_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();

            $table->string('slot', 64);

            $table->unsignedSmallInteger('quantity')->default(1);

            $table->boolean('is_required')->default(true);
            $table->boolean('is_replaceable')->default(true);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['system_id', 'slot', 'product_variant_id'], 'system_components_slot_variant_unique');

            $table->index(['system_id', 'slot', 'sort_order'], 'system_components_listing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_components');
    }
};
