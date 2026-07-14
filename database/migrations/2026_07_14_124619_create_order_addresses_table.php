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
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');

            $table->string('type', 16);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company')->nullable();
            $table->string('vat_number', 64)->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 32);
            $table->string('city');
            $table->string('state')->nullable();
            $table->char('country_code', 2);
            $table->string('email');
            $table->string('phone', 64)->nullable();

            $table->timestamps();

            $table->unique(['order_id', 'type'], 'order_address_type_uq');

            $table->foreign('order_id', 'order_address_order_fk')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_addresses');
    }
};
