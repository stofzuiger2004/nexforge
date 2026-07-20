<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Exceptions\StaleResourceVersionException;
use App\Models\PriceList;
use App\Models\System;
use App\Models\SystemPrice;
use App\Models\SystemPriceHistory;
use App\Models\User;
use App\Support\Money\DecimalMoney;
use DomainException;
use Illuminate\Support\Facades\DB;

final class SystemPriceUpdater
{
    public function update(System $system, PriceList $priceList, string $amount, ?string $compareAtAmount, string $reason, int $expectedLockVersion, User $actor): SystemPrice {
        $amountInCents = DecimalMoney::toCents($amount);
        $compareAtInCents = $compareAtAmount === null
            || trim($compareAtAmount) === ''
                ? null
                : DecimalMoney::toCents($compareAtAmount);

        if ($amountInCents < 1) {
            throw new DomainException('The system package price must be at least 0.01.',);
        }

        if ($compareAtInCents !== null && $compareAtInCents <= $amountInCents){
            throw new DomainException('The compare-at price must be higher than the system package price.',);
        }

        return DB::transaction(
            function () use (
                $system,
                $priceList,
                $amountInCents,
                $compareAtInCents,
                $reason,
                $expectedLockVersion,
                $actor,
            ): SystemPrice {
                $price = SystemPrice::query()
                    ->where('system_id', $system->getKey())
                    ->where('price_list_id', $priceList->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($price->lock_version !== $expectedLockVersion) {
                    throw StaleResourceVersionException::forSystemPrice();
                }

                $oldAmount = $price->amount_in_cents;
                $oldCompareAt = $price->compare_at_amount_in_cents;

                if (
                    $oldAmount === $amountInCents
                    && $oldCompareAt === $compareAtInCents
                ) {
                    return $price;
                }

                $nextVersion = $price->lock_version + 1;

                $price->forceFill([
                    'amount_in_cents' => $amountInCents,
                    'compare_at_amount_in_cents' => $compareAtInCents,
                    'lock_version' => $nextVersion,
                ])->saveOrFail();

                SystemPriceHistory::query()->create([
                    'system_price_id' => $price->id,
                    'system_id' => $system->id,
                    'price_list_id' => $priceList->id,
                    'actor_user_id' => $actor->id,
                    'old_amount_in_cents' => $oldAmount,
                    'new_amount_in_cents' => $amountInCents,
                    'old_compare_at_amount_in_cents' => $oldCompareAt,
                    'new_compare_at_amount_in_cents' => $compareAtInCents,
                    'lock_version_before' => $expectedLockVersion,
                    'lock_version_after' => $nextVersion,
                    'reason' => $reason,
                    'changed_at' => now(),
                ]);

                return $price->refresh();
            },
            attempts: 3,
        );
    }
}
