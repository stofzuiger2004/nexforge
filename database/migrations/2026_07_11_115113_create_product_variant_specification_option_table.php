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
        Schema::create('product_variant_specification_option', function (Blueprint $table) {
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('specification_option_id');

            $table->timestamps();

            $table->foreign('product_variant_id', 'pv_spec_option_variant_fk')->references('id')->on('product_variants')->cascadeOnDelete();

            $table->foreign('specification_option_id', 'pv_spec_option_option_fk')->references('id')->on('specification_options')->cascadeOnDelete();

            $table->primary(['product_variant_id', 'specification_option_id'], 'pv_spec_option_pk');

            $table->index('specification_option_id', 'pv_spec_option_option_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_specification_option');
    }
};
