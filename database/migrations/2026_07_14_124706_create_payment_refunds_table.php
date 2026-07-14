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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->unsignedBigInteger('payment_id');
            $table->unsignedBigInteger('requested_by_user_id')->nullable();

            $table->string('provider', 32)->default('mollie');
            $table->string('provider_refund_id', 100)->nullable();
            $table->string('status', 32)->default('creating');

            $table->unsignedBigInteger('amount_in_cents');
            $table->char('currency', 3);
            $table->string('description')->nullable();
            $table->string('reason')->nullable();
            $table->string('idempotency_key', 120);

            $table->timestamp('provider_created_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->json('provider_snapshot')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'payment_refund_public_uq');
            $table->unique(['provider', 'provider_refund_id'], 'payment_refund_provider_uq');
            $table->unique('idempotency_key', 'payment_refund_idempotency_uq');

            $table->index(['payment_id', 'status'], 'payment_refund_status_idx');

            $table->foreign('payment_id', 'payment_refund_payment_fk')->references('id')->on('payments')->restrictOnDelete();
            $table->foreign('requested_by_user_id', 'payment_refund_user_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
