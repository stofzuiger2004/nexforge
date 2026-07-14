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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->string('order_number', 64);
            $table->unsignedBigInteger('user_id')->nullable();

            $table->char('guest_token_hash', 64)->nullable();

            $table->string('status', 32)->default('draft');
            $table->string('payment_status', 32)->default('unpaid');
            $table->string('fulfillment_status', 32)->default('unfulfilled');
            $table->string('customer_email');
            $table->string('customer_locale', 10)->default('nl_BE');

            $table->char('currency', 3);

            $table->unsignedBigInteger('subtotal_in_cents')->default(0);
            $table->bigInteger('adjustment_total_in_cents')->default(0);
            $table->unsignedBigInteger('shipping_in_cents')->default(0);
            $table->unsignedBigInteger('tax_in_cents')->default(0);
            $table->unsignedBigInteger('total_in_cents')->default(0);
            $table->unsignedBigInteger('paid_in_cents')->default(0);
            $table->unsignedBigInteger('refunded_in_cents')->default(0);
            $table->unsignedBigInteger('charged_back_in_cents')->default(0);
            $table->unsignedInteger('lock_version')->default(1);

            $table->timestamp('placed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'order_public_uq');
            $table->unique('order_number', 'order_number_uq');
            $table->unique('guest_token_hash', 'order_guest_token_uq');

            $table->index(['user_id', 'created_at'], 'order_user_date_idx');
            $table->index(['status', 'created_at'], 'order_status_date_idx');
            $table->index(['payment_status', 'created_at'], 'order_payment_date_idx');
            $table->index('customer_email', 'order_email_idx');

            $table->foreign('user_id', 'order_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
