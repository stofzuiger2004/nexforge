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
        Schema::table('variant_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('lock_version')->default(1)->after('compare_at_amount_in_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_prices', function (Blueprint $table) {
            $table->dropColumn('lock_version');
        });
    }
};
