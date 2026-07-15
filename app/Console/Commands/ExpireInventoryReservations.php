<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\InventoryReservation;
use App\Services\Configurations\ConfigurationReviewService;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Console\Command;
use Throwable;

class ExpireInventoryReservations extends Command
{
    protected $signature =
        'inventory:expire-reservations
        {--limit=500 : Maximum reservations to process}';

    protected $description =
        'Release stock held by expired inventory reservations';

    public function handle(
        InventoryReservationService $reservationService,
        ConfigurationReviewService $reviewService
    ): int {
        $limit = max(
            1,
            (int) $this->option('limit'),
        );

        $reservationIds =
            InventoryReservation::query()
                ->dueForExpiration()
                ->orderBy('id')
                ->limit($limit)
                ->pluck('id');

        $expiredCount = 0;
        $errorCount = 0;

        foreach ($reservationIds as $reservationId) {
            try {
                $reservation =
                    InventoryReservation::query()
                        ->find($reservationId);

                if ($reservation === null) {
                    continue;
                }

                $result =
                    $reservationService
                        ->expire($reservation);

                $reviewService->unlockAfterReservationEnd($result);

                if (
                    $result->status->value
                    === 'expired'
                ) {
                    $expiredCount++;
                }
            } catch (Throwable $exception) {
                $errorCount++;

                report($exception);

                $this->error(sprintf(
                    'Reservation %d failed: %s',
                    $reservationId,
                    $exception->getMessage(),
                ));
            }
        }

        $this->info(sprintf(
            'Expired %d reservation(s); %d error(s).',
            $expiredCount,
            $errorCount,
        ));

        return $errorCount === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
