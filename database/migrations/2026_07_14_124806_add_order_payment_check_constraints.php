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
            ALTER TABLE order_items
            ADD CONSTRAINT order_item_qty_chk
            CHECK (quantity > 0)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE order_item_components
            ADD CONSTRAINT order_component_qty_chk
            CHECK (quantity > 0)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE payments
            ADD CONSTRAINT payment_amount_chk
            CHECK (amount_in_cents > 0)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE payment_refunds
            ADD CONSTRAINT payment_refund_amount_chk
            CHECK (amount_in_cents > 0)
            SQL
        );

        DB::statement(
            <<<'SQL'
            ALTER TABLE payment_chargebacks
            ADD CONSTRAINT payment_chargeback_amount_chk
            CHECK (amount_in_cents > 0)
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
            'ALTER TABLE payment_chargebacks '
            .'DROP CHECK payment_chargeback_amount_chk',
        );

        DB::statement(
            'ALTER TABLE payment_refunds '
            .'DROP CHECK payment_refund_amount_chk',
        );

        DB::statement(
            'ALTER TABLE payments '
            .'DROP CHECK payment_amount_chk',
        );

        DB::statement(
            'ALTER TABLE order_item_components '
            .'DROP CHECK order_component_qty_chk',
        );

        DB::statement(
            'ALTER TABLE order_items '
            .'DROP CHECK order_item_qty_chk',
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
