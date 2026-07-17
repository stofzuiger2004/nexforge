<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;

final class AdminInventoryQueryService
{
    private const AVAILABLE_SQL = <<<'SQL'
        (inventory_items.quantity_on_hand - inventory_items.quantity_reserved)
        SQL;

    /**
     * @param  Builder<InventoryItem>  $query
     * @param  array{
     *     search: string|null,
     *     warehouse_id: int|null,
     *     stock_state: string|null,
     *     sort: string,
     *     per_page: int
     * }  $filters
     * @return Builder<InventoryItem>
     */
    public function apply(
        Builder $query,
        array $filters,
    ): Builder {
        $query
            ->when(
                $filters['search'] !== null,
                function (
                    Builder $query,
                ) use ($filters): void {
                    $search = '%'
                        .$filters['search']
                        .'%';

                    $query->where(
                        function (
                            Builder $query,
                        ) use ($search): void {
                            $query
                                ->whereHas(
                                    'variant',
                                    function (
                                        Builder $query,
                                    ) use ($search): void {
                                        $query
                                            ->where(
                                                'sku',
                                                'like',
                                                $search,
                                            )
                                            ->orWhere(
                                                'name',
                                                'like',
                                                $search,
                                            )
                                            ->orWhereHas(
                                                'product',
                                                function (
                                                    Builder $query,
                                                ) use ($search): void {
                                                    $query
                                                        ->where(
                                                            'name',
                                                            'like',
                                                            $search,
                                                        )
                                                        ->orWhereHas(
                                                            'brand',
                                                            fn (
                                                                Builder $query,
                                                            ) => $query
                                                                ->where(
                                                                    'name',
                                                                    'like',
                                                                    $search,
                                                                ),
                                                        );
                                                },
                                            );
                                    },
                                )
                                ->orWhere(
                                    'bin_location',
                                    'like',
                                    $search,
                                )
                                ->orWhereHas(
                                    'warehouse',
                                    fn (
                                        Builder $query,
                                    ) => $query
                                        ->where(
                                            'name',
                                            'like',
                                            $search,
                                        )
                                        ->orWhere(
                                            'code',
                                            'like',
                                            $search,
                                        ),
                                );
                        },
                    );
                },
            )
            ->when(
                $filters['warehouse_id'] !== null,
                fn (
                    Builder $query,
                ) => $query->where(
                    'warehouse_id',
                    $filters['warehouse_id'],
                ),
            );

        $this->applyStockState(
            $query,
            $filters['stock_state'],
        );

        $this->applySort(
            $query,
            $filters['sort'],
        );

        return $query;
    }

    /**
     * @param Builder<InventoryItem> $query
     */
    private function applyStockState(
        Builder $query,
        ?string $stockState,
    ): void {
        match ($stockState) {
            'in_stock' => $query
                ->where('is_active', true)
                ->whereRaw(
                    self::AVAILABLE_SQL.' > 0',
                )
                ->where(
                    function (
                        Builder $query,
                    ): void {
                        $query
                            ->whereNull('reorder_point')
                            ->orWhereRaw(
                                self::AVAILABLE_SQL
                                .' > inventory_items.reorder_point',
                            );
                    },
                ),

            'low_stock' => $query
                ->where('is_active', true)
                ->whereNotNull('reorder_point')
                ->whereRaw(
                    self::AVAILABLE_SQL.' > 0',
                )
                ->whereRaw(
                    self::AVAILABLE_SQL
                    .' <= inventory_items.reorder_point',
                ),

            'out_of_stock' => $query
                ->where('is_active', true)
                ->whereRaw(
                    self::AVAILABLE_SQL.' <= 0',
                ),

            'inactive' => $query
                ->where('is_active', false),

            default => null,
        };
    }

    /**
     * @param Builder<InventoryItem> $query
     */
    private function applySort(
        Builder $query,
        string $sort,
    ): void {
        match ($sort) {
            'available_asc' => $query
                ->orderByRaw(
                    self::AVAILABLE_SQL.' ASC',
                )
                ->orderBy('id'),

            'available_desc' => $query
                ->orderByRaw(
                    self::AVAILABLE_SQL.' DESC',
                )
                ->orderBy('id'),

            'updated_desc' => $query
                ->latest('updated_at')
                ->latest('id'),

            default => $query
                ->orderBy(
                    'product_variant_id',
                )
                ->orderBy('warehouse_id')
                ->orderBy('id'),
        };
    }
}