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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->unsignedBigInteger('order_id');

            $table->string('provider', 32)->default('mollie');
            $table->string('provider_payment_id', 100)->nullable();
            $table->string('provider_profile_id', 100)->nullable();

            $table->unsignedSmallInteger('attempt_number');

            $table->string('status', 32)->default('creating');
            $table->string('provider_status', 32)->nullable();
            $table->string('mode', 16)->nullable();
            $table->string('method', 64)->nullable();
            $table->string('capture_mode', 32)->nullable();
            $table->string('sequence_type', 32)->default('oneoff');

            $table->unsignedBigInteger('amount_in_cents');

            $table->char('currency', 3);
            $table->string('description');
            $table->text('checkout_url')->nullable();
            $table->string('idempotency_key', 120);

            $table->unsignedBigInteger('amount_refunded_in_cents')->default(0);
            $table->unsignedBigInteger('amount_charged_back_in_cents')->default(0);

            $table->timestamp('provider_created_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->string('failure_code', 100)->nullable();
            $table->text('failure_message')->nullable();

            $table->json('metadata')->nullable();
            $table->json('provider_snapshot')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'payment_public_uq');
            $table->unique(['provider', 'provider_payment_id'], 'payment_provider_id_uq');
            $table->unique(['order_id', 'attempt_number'], 'payment_attempt_uq');
            $table->unique('idempotency_key', 'payment_idempotency_uq');

            $table->index(['order_id', 'status'], 'payment_order_status_idx');
            $table->index(['provider', 'provider_status'], 'payment_provider_status_idx');

            $table->foreign('order_id', 'payment_order_fk')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
