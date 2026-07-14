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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32);
            $table->string('name');

            $table->unsignedSmallInteger('priority')->default(100);

            $table->boolean('is_active')->default(true);
            $table->boolean('can_assemble_systems')->default(true);
            $table->boolean('can_fulfill_orders')->default(true);

            $table->string('timezone', 64)->default('UTC');

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->char('country_code', 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('code', 'wh_code_uq');

            $table->index(['is_active', 'can_assemble_systems', 'priority'], 'wh_assembly_idx');

            $table->index(['is_active', 'can_fulfill_orders', 'priority'], 'wh_fulfillment_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
