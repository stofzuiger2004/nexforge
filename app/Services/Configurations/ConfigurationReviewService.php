<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Enums\ConfigurationStatus;
use App\Enums\InventoryReservationStatus;
use App\Enums\ValidationRunStatus;
use App\Models\Configuration;
use App\Models\InventoryReservation;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ConfigurationReviewService
{
    public function __construct(
        private readonly ConfigurationValidator $validator,

        private readonly InventoryReservationService $inventoryReservations,
    ) {}

    public function prepare(
        Configuration $configuration,
        ?User $actor = null,
    ): InventoryReservation {
        return DB::transaction(
            function () use (
                $configuration,
                $actor,
            ): InventoryReservation {
                $lockedConfiguration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $configuration->id,
                        );

                if (
                    in_array(
                        $lockedConfiguration->status,
                        [
                            ConfigurationStatus::Converted,
                            ConfigurationStatus::Expired,
                        ],
                        true,
                    )
                ) {
                    throw new DomainException(
                        'This configuration can no longer enter checkout.',
                    );
                }

                /*
                 * A repeated request may already have prepared
                 * this exact configuration version.
                 */
                if (
                    $lockedConfiguration->status
                    === ConfigurationStatus::ReadyForCheckout
                ) {
                    $existingReservation =
                        InventoryReservation::query()
                            ->where(
                                'configuration_id',
                                $lockedConfiguration->id,
                            )
                            ->where(
                                'configuration_version',
                                $lockedConfiguration->version,
                            )
                            ->whereNull('order_id')
                            ->where(
                                'status',
                                InventoryReservationStatus::Active
                                    ->value,
                            )
                            ->latest('id')
                            ->lockForUpdate()
                            ->first();

                    if (
                        $existingReservation !== null
                        && ! $existingReservation->isExpired()
                    ) {
                        return $existingReservation->load([
                            'warehouse',
                            'items.inventoryItem.variant.product',
                        ]);
                    }

                    if (
                        $existingReservation !== null
                        && $existingReservation->isExpired()
                    ) {
                        $this->inventoryReservations
                            ->expire(
                                $existingReservation,
                            );
                    }

                    /*
                     * Temporarily restore the last validated state
                     * so validation and reservation can run again.
                     */
                    $this->restoreEditableState(
                        $lockedConfiguration->refresh(),
                    );
                }

                /*
                 * Always perform authoritative validation directly
                 * before reserving inventory.
                 */
                $this->validator->validate(
                    $lockedConfiguration,
                );

                $lockedConfiguration->refresh();

                if (
                    $lockedConfiguration->status
                    !== ConfigurationStatus::Valid
                ) {
                    throw new DomainException(
                        'Resolve the compatibility issues before reviewing this configuration.',
                    );
                }

                $reservation =
                    $this->inventoryReservations
                        ->reserveConfiguration(
                            configuration: $lockedConfiguration,

                            actor: $actor,
                        );

                $lockedConfiguration->forceFill([
                    'status' => ConfigurationStatus::ReadyForCheckout,

                    'last_activity_at' => now(),
                ])->saveOrFail();

                return $reservation->fresh([
                    'warehouse',
                    'items.inventoryItem.variant.product',
                ]);
            },
            attempts: 3,
        );
    }

    public function resumeEditing(
        Configuration $configuration,
        ?User $actor = null,
    ): Configuration {
        return DB::transaction(
            function () use (
                $configuration,
                $actor,
            ): Configuration {
                $lockedConfiguration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $configuration->id,
                        );

                if (
                    in_array(
                        $lockedConfiguration->status,
                        [
                            ConfigurationStatus::Converted,
                            ConfigurationStatus::Expired,
                        ],
                        true,
                    )
                ) {
                    throw new DomainException(
                        'This configuration can no longer be edited.',
                    );
                }

                $reservations =
                    InventoryReservation::query()
                        ->where(
                            'configuration_id',
                            $lockedConfiguration->id,
                        )
                        ->whereNull('order_id')
                        ->where(
                            'status',
                            InventoryReservationStatus::Active
                                ->value,
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                foreach ($reservations as $reservation) {
                    $this->inventoryReservations
                        ->cancel(
                            reservation: $reservation,
                            actor: $actor,

                            reason: 'Customer returned to configuration editing.',
                        );
                }

                $this->restoreEditableState(
                    $lockedConfiguration->refresh(),
                );

                return $lockedConfiguration->fresh([
                    'items.variant.product',
                    'adjustments',
                ]);
            },
            attempts: 3,
        );
    }

    /*
     * Called after the expiration command releases a review
     * reservation. It prevents a configuration from remaining
     * permanently frozen after its stock hold expires.
     */
    public function unlockAfterReservationEnd(
        InventoryReservation $reservation,
    ): void {
        if ($reservation->order_id !== null) {
            return;
        }

        if (
            ! in_array(
                $reservation->status,
                [
                    InventoryReservationStatus::Released,
                    InventoryReservationStatus::Expired,
                    InventoryReservationStatus::Cancelled,
                ],
                true,
            )
        ) {
            return;
        }

        DB::transaction(
            function () use ($reservation): void {
                $configuration =
                    Configuration::query()
                        ->lockForUpdate()
                        ->find(
                            $reservation->configuration_id,
                        );

                if (
                    $configuration === null
                    || $configuration->status
                        !== ConfigurationStatus::ReadyForCheckout
                ) {
                    return;
                }

                $stillHasActiveReservation =
                    InventoryReservation::query()
                        ->where(
                            'configuration_id',
                            $configuration->id,
                        )
                        ->whereNull('order_id')
                        ->where(
                            'status',
                            InventoryReservationStatus::Active
                                ->value,
                        )
                        ->exists();

                if ($stillHasActiveReservation) {
                    return;
                }

                $this->restoreEditableState(
                    $configuration,
                );
            },
            attempts: 3,
        );
    }

    private function restoreEditableState(
        Configuration $configuration,
    ): void {
        $validationRun = $configuration
            ->validationRuns()
            ->latest('id')
            ->first();

        $hasCurrentPassingValidation =
            $validationRun !== null
            && $validationRun
                ->configuration_version
                === $configuration->version
            && in_array(
                $validationRun->status,
                [
                    ValidationRunStatus::Passed,
                    ValidationRunStatus::PassedWithWarnings,
                ],
                true,
            );

        $configuration->forceFill([
            'status' => $hasCurrentPassingValidation
                    ? ConfigurationStatus::Valid
                    : ConfigurationStatus::Draft,

            'validated_at' => $hasCurrentPassingValidation
                    ? $validationRun->completed_at
                    : null,

            'last_activity_at' => now(),
        ])->saveOrFail();
    }
}
