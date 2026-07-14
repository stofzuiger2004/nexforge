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
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('configuration_id');
            $table->timestamp('committed_at')->nullable()->after('reserved_at');
            $table->index(['order_id', 'status'], 'inv_res_order_status_idx');
            $table->foreign('order_id', 'inv_res_order_fk')->references('id')->on('orders')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropForeign('inv_res_order_fk');
            $table->dropIndex('inv_res_order_status_idx');
            $table->dropColumn(['order_id', 'committed_at']);
        });
    }
};
