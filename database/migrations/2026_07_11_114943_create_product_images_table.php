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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('disk', 64)->default('public');
            $table->string('path');
            $table->string('alt_text')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_idx');

            $table->index(['product_variant_id', 'sort_order'], 'product_images_variant_sort_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
