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
        Schema::create('category_specification', function (Blueprint $table) {

            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            $table->foreignId('specification_id')->constrained()->cascadeOnDelete();

            $table->boolean('is_required')->default(false);

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->primary(['category_id', 'specification_id'], 'category_specification_pk');

            $table->index('specification_id', 'category_specification_spec_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_specification');
    }
};
