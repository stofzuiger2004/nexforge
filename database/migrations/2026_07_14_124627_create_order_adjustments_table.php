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
        Schema::create('order_adjustments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_item_id')->nullable();
            $table->unsignedSmallInteger('line_number');

            $table->string('type', 32);
            $table->string('code', 100)->nullable();
            $table->string('label');

            $table->bigInteger('amount_in_cents');
            $table->bigInteger('tax_in_cents')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['order_id', 'line_number'], 'order_adjustment_line_uq');

            $table->index(['order_id', 'type'], 'order_adjustment_type_idx');

            $table->foreign('order_id', 'order_adjustment_order_fk')->references('id')->on('orders')->restrictOnDelete();
            $table->foreign('order_item_id', 'order_adjustment_item_fk')->references('id')->on('order_items')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_adjustments');
    }
};
