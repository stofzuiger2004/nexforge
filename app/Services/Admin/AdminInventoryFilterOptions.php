<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Warehouse;

final class AdminInventoryFilterOptions
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'warehouses' => Warehouse::query()
                ->orderBy('priority')
                ->orderBy('name')
                ->get([
                    'id',
                    'code',
                    'name',
                ])
                ->map(
                    static fn (
                        Warehouse $warehouse,
                    ): array => [
                        'value' => $warehouse->id,
                        'label' => $warehouse->name
                            .' ('
                            .$warehouse->code
                            .')',
                    ],
                )
                ->values()
                ->all(),

            'stock_states' => [
                [
                    'value' => 'in_stock',
                    'label' => 'In stock',
                ],
                [
                    'value' => 'low_stock',
                    'label' => 'Low stock',
                ],
                [
                    'value' => 'out_of_stock',
                    'label' => 'Out of stock',
                ],
                [
                    'value' => 'inactive',
                    'label' => 'Inactive',
                ],
            ],

            'sorts' => [
                [
                    'value' => 'sku_asc',
                    'label' => 'SKU',
                ],
                [
                    'value' => 'available_asc',
                    'label' => 'Available: low to high',
                ],
                [
                    'value' => 'available_desc',
                    'label' => 'Available: high to low',
                ],
                [
                    'value' => 'updated_desc',
                    'label' => 'Recently updated',
                ],
            ],
        ];
    }
}