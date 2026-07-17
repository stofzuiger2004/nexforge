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
        Schema::create('variant_price_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('variant_price_id');
            $table->unsignedBigInteger('product_variant_id');
            $table->unsignedBigInteger('price_list_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('old_amount_in_cents');
            $table->unsignedBigInteger('new_amount_in_cents');
            $table->unsignedBigInteger('old_compare_at_amount_in_cents')->nullable();
            $table->unsignedBigInteger('new_compare_at_amount_in_cents')->nullable();
            $table->unsignedInteger('lock_version_before');
            $table->unsignedInteger('lock_version_after');

            $table->string('reason', 500);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['variant_price_id','changed_at'],'vph_price_changed_idx');
            $table->index(['product_variant_id','price_list_id'],'vph_variant_list_idx');

            $table->foreign('variant_price_id','vph_price_fk')->references('id')->on('variant_prices')->restrictOnDelete();
            $table->foreign('product_variant_id','vph_variant_fk')->references('id')->on('product_variants')->restrictOnDelete();
            $table->foreign('price_list_id','vph_list_fk')->references('id')->on('price_lists')->restrictOnDelete();
            $table->foreign('actor_user_id','vph_actor_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_price_histories');
    }
};
