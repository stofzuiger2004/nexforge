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
        Schema::create('payment_chargebacks', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->unsignedBigInteger('payment_id');

            $table->string('provider', 32)->default('mollie');
            $table->string('provider_chargeback_id', 100);
            $table->string('status', 24)->default('received');

            $table->unsignedBigInteger('amount_in_cents');

            $table->char('currency', 3);
            $table->string('reason_code', 100)->nullable();
            $table->text('reason_description')->nullable();

            $table->timestamp('provider_created_at');
            $table->timestamp('reversed_at')->nullable();

            $table->json('provider_snapshot')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'payment_chargeback_public_uq');
            $table->unique(['provider', 'provider_chargeback_id'], 'payment_chargeback_provider_uq');

            $table->index(['payment_id', 'status'], 'payment_chargeback_status_idx');

            $table->foreign('payment_id', 'payment_chargeback_payment_fk')->references('id')->on('payments')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_chargebacks');
    }
};
