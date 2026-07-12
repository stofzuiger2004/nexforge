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
        Schema::create('product_variant_specification_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specification_id')->constrained()->cascadeOnDelete();

            $table->text('value_text')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_decimal', 18, 4)->nullable();
            $table->boolean('value_boolean')->nullable();

            $table->timestamps();

            $table->unique(['product_variant_id', 'specification_id'], 'pv_spec_values_variant_spec_unique');

            $table->index(['specification_id', 'value_integer'], 'pv_spec_values_integer_idx');
            $table->index(['specification_id', 'value_decimal'], 'pv_spec_values_decimal_idx');
            $table->index(['specification_id', 'value_boolean'], 'pv_spec_values_boolean_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_specification_values');
    }
};
