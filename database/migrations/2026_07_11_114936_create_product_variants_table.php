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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('sku')->unique();
            $table->string('name')->nullable();

            $table->string('manufacturer_part_number')->nullable()->index();

            $table->string('barcode')->nullable()->unique();

            $table->string('status', 32)->default('draft');
            $table->boolean('is_default')->default(false);
            $table->boolean('track_inventory')->default(true);

            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('width_mm')->nullable();
            $table->unsignedInteger('height_mm')->nullable();
            $table->unsignedInteger('depth_mm')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'status'], 'product_variants_product_status_idx');

            $table->index(['product_id', 'is_default'], 'product_variants_default_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
