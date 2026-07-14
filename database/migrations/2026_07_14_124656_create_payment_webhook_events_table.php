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
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id');

            $table->string('provider', 32)->default('mollie');
            $table->string('provider_event_id', 120)->nullable();
            $table->string('event_type', 100)->nullable();
            $table->string('resource_type', 64)->default('payment');
            $table->string('provider_resource_id', 120);
            $table->unsignedBigInteger('payment_id')->nullable();

            $table->json('payload');
            $table->char('payload_hash', 64);

            $table->timestamp('signature_verified_at')->nullable();

            $table->string('processing_status', 24)->default('received');

            $table->unsignedSmallInteger('attempt_count')->default(0);

            $table->timestamp('received_at');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique('public_id', 'payment_webhook_public_uq');
            $table->unique(['provider', 'provider_event_id'], 'payment_webhook_event_uq');

            $table->index(['provider', 'provider_resource_id'], 'payment_webhook_resource_idx');
            $table->index(['processing_status', 'received_at'], 'payment_webhook_status_idx');
            $table->index('payment_id', 'payment_webhook_payment_idx');

            $table->foreign('payment_id', 'payment_webhook_payment_fk')->references('id')->on('payments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
