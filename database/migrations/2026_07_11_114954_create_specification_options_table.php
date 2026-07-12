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
        Schema::create('specification_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('specification_id')->constrained()->cascadeOnDelete();

            $table->string('value');
            $table->string('label');

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['specification_id', 'value'], 'specification_options_value_unique');

            $table->index(['specification_id', 'is_active', 'sort_order'], 'specification_options_listing_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specification_options');
    }
};
