<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        DB::statement(
            <<<'SQL'
            ALTER TABLE inventory_items
            ADD CONSTRAINT inv_item_reserved_chk
            CHECK (quantity_reserved <= quantity_on_hand)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE inventory_reservations
            ADD CONSTRAINT inv_res_expiry_chk
            CHECK (expires_at > reserved_at)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE inventory_reservation_items
            ADD CONSTRAINT inv_res_item_qty_chk
            CHECK (
                released_quantity + consumed_quantity
                <= quantity
            )
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE inventory_movements
            ADD CONSTRAINT inv_move_nonzero_chk
            CHECK (
                on_hand_delta <> 0
                OR reserved_delta <> 0
            )
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE inventory_movements
            ADD CONSTRAINT inv_move_balance_chk
            CHECK (
                reserved_after <= on_hand_after
            )
            SQL
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        DB::statement(
            'ALTER TABLE inventory_movements '
            .'DROP CHECK inv_move_balance_chk',
        );

        DB::statement(
            'ALTER TABLE inventory_movements '
            .'DROP CHECK inv_move_nonzero_chk',
        );

        DB::statement(
            'ALTER TABLE inventory_reservation_items '
            .'DROP CHECK inv_res_item_qty_chk',
        );

        DB::statement(
            'ALTER TABLE inventory_reservations '
            .'DROP CHECK inv_res_expiry_chk',
        );

        DB::statement(
            'ALTER TABLE inventory_items '
            .'DROP CHECK inv_item_reserved_chk',
        );
    }

    private function supportsCheckConstraints(): bool
    {
        if (
            DB::connection()->getDriverName()
            !== 'mysql'
        ) {
            return false;
        }

        $result = DB::selectOne(
            'SELECT VERSION() AS version',
        );

        $version = strtolower(
            (string) ($result->version ?? ''),
        );

        /*
         * This migration targets Oracle MySQL.
         * The service layer still protects the invariants
         * when checks are skipped.
         */
        if (str_contains($version, 'mariadb')) {
            return false;
        }

        if (
            preg_match(
                '/^(\d+\.\d+\.\d+)/',
                $version,
                $matches,
            ) !== 1
        ) {
            return false;
        }

        return version_compare(
            $matches[1],
            '8.0.16',
            '>=',
        );
    }
};
