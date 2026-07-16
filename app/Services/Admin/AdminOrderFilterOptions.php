<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use UnitEnum;

final class AdminOrderFilterOptions
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'statuses' => $this->enumOptions(
                OrderStatus::cases(),
            ),

            'payment_statuses' => $this->enumOptions(
                OrderPaymentStatus::cases(),
            ),

            'fulfillment_statuses' => $this->enumOptions(
                OrderFulfillmentStatus::cases(),
            ),

            'sorts' => [
            [
                'value' => 'newest',
                'label' => 'Newest first',
            ],
            [
                'value' => 'oldest',
                'label' => 'Oldest first',
            ],
            [
                'value' => 'total_high',
                'label' => 'Highest total first',
            ],
            [
                'value' => 'total_low',
                'label' => 'Lowest total first',
            ],
            ],

            'per_page' => [
            15,
            25,
            50,
            100,
            ],
        ];
    }

    /**
     * @param  array<int, UnitEnum>  $cases
     * @return array<int, array{
     *     value: string,
     *     label: string
     * }>
     */
    private function enumOptions(
        array $cases,
    ): array {
        return array_map(
            static fn (
                UnitEnum $case,
            ): array => [
                'value' => $case->value,

                'label' => Str::headline(
                    $case->value,
                ),
            ],
            $cases,
        );
    }
}
