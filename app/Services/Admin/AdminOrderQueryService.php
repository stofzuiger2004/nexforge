<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class AdminOrderQueryService
{
    /**
     * @param  Builder<Order>  $query
     * @param array{
     *     search: string|null,
     *     status: string|null,
     *     payment_status: string|null,
     *     fulfillment_status: string|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     sort: string,
     *     per_page: int
     * } $filters
     * @return Builder<Order>
     */
    public function apply(
        Builder $query,
        array $filters,
    ): Builder {
        $query
            ->when(
                $filters['search'],
                function (
                    Builder $query,
                    string $search,
                ): void {
                    $pattern =
                        '%'.$search.'%';

                    $query->where(
                        function (
                            Builder $query,
                        ) use ($pattern): void {
                            $query
                                ->where(
                                    'order_number',
                                    'like',
                                    $pattern,
                                )
                                ->orWhere(
                                    'customer_email',
                                    'like',
                                    $pattern,
                                )
                                ->orWhereHas(
                                    'user',
                                    static function (
                                        Builder $query,
                                    ) use (
                                        $pattern,
                                    ): void {
                                        $query
                                            ->where(
                                                'name',
                                                'like',
                                                $pattern,
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                $pattern,
                                            );
                                    },
                                )
                                ->orWhereHas(
                                    'payments',
                                    static function (
                                        Builder $query,
                                    ) use (
                                        $pattern,
                                    ): void {
                                        $query->where(
                                            'provider_payment_id',
                                            'like',
                                            $pattern,
                                        );
                                    },
                                );
                        },
                    );
                },
            )
            ->when(
                $filters['status'],
                static fn (
                    Builder $query,
                    string $status,
                ) => $query->where(
                    'status',
                    $status,
                ),
            )
            ->when(
                $filters['payment_status'],
                static fn (
                    Builder $query,
                    string $status,
                ) => $query->where(
                    'payment_status',
                    $status,
                ),
            )
            ->when(
                $filters['fulfillment_status'],
                static fn (
                    Builder $query,
                    string $status,
                ) => $query->where(
                    'fulfillment_status',
                    $status,
                ),
            )
            ->when(
                $filters['date_from'],
                static function (
                    Builder $query,
                    string $date,
                ): void {
                    $query->where(
                        'placed_at',
                        '>=',
                        CarbonImmutable::parse(
                            $date,
                        )->startOfDay(),
                    );
                },
            )
            ->when(
                $filters['date_to'],
                static function (
                    Builder $query,
                    string $date,
                ): void {
                    $query->where(
                        'placed_at',
                        '<',
                        CarbonImmutable::parse(
                            $date,
                        )
                            ->addDay()
                            ->startOfDay(),
                    );
                },
            );

        return match ($filters['sort']) {
            'oldest' => $query->orderBy(
                'placed_at',
                'asc',
            ),

            'total_high' => $query
                ->orderBy(
                    'total_in_cents',
                    'desc',
                )
                ->orderByDesc('id'),

            'total_low' => $query
                ->orderBy(
                    'total_in_cents',
                    'asc',
                )
                ->orderByDesc('id'),

            default => $query
                ->orderByDesc('placed_at')
                ->orderByDesc('id'),
        };
    }
}
