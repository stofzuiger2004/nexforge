<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Enums\ComponentSlot;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfiguratorComponentRequest;
use App\Models\Configuration;
use App\Models\ProductVariant;
use App\Models\SystemComponent;
use App\Services\Configurations\ConfigurationAccessService;
use App\Services\Configurations\ConfigurationEditor;
use App\Services\Configurations\ConfigurationValidator;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConfiguratorComponentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
    UpdateConfiguratorComponentRequest $request,
    Configuration $configuration,
    ComponentSlot $slot,
    ConfigurationAccessService $access,
    ConfigurationEditor $editor,
    ConfigurationValidator $validator,
): RedirectResponse {
    $access->assertCanAccess(
        $request,
        $configuration,
    );

    if (! $configuration->status->isEditable()) {
        abort(
            409,
            'This configuration can no longer be changed.',
        );
    }

    $systemComponent = SystemComponent::query()
        ->where(
            'system_id',
            $configuration->source_system_id,
        )
        ->where(
            'slot',
            $slot->value,
        )
        ->orderBy('sort_order')
        ->firstOrFail();

    if (! $systemComponent->is_replaceable) {
        throw ValidationException::withMessages([
            'variant_id' =>
                'This component is fixed for the selected system.',
        ]);
    }

    if ($slot->allowsMultiple()) {
        throw ValidationException::withMessages([
            'variant_id' =>
                'Multiple-selection slots require the quantity interface.',
        ]);
    }

    $validated = $request->validated();

    /*
     * A null variant means that the customer selected
     * "No operating system".
     */
    if ($validated['variant_id'] === null) {
        if (
            ! $slot->allowsNone()
            || $systemComponent->is_required
        ) {
            throw ValidationException::withMessages([
                'variant_id' =>
                    'This component may not be removed.',
            ]);
        }

        $currentItem = $configuration
            ->items()
            ->where(
                'slot',
                $slot->value,
            )
            ->first();

        if ($currentItem === null) {
            return to_route(
                'configurator.show',
                $configuration,
            );
        }

        try {
            $updatedConfiguration =
                $editor->removeComponent(
                    configuration: $configuration,
                    slot: $slot,
                );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'variant_id' =>
                    $exception->getMessage(),
            ]);
        }

        $validator->validate(
            $updatedConfiguration,
        );

        return to_route(
            'configurator.show',
            $configuration,
        )->with(
            'configuration_updated',
            true,
        );
    }

    $variant = ProductVariant::query()
        ->findOrFail(
            $validated['variant_id'],
        );

    $currentItem = $configuration
        ->items()
        ->where(
            'slot',
            $slot->value,
        )
        ->first();

    $quantity = max(
        1,
        (int) (
            $validated['quantity']
            ?? $currentItem?->quantity
            ?? $systemComponent->quantity
            ?? 1
        ),
    );

    if (
        $currentItem !== null
        && $currentItem->product_variant_id
            === $variant->id
        && $currentItem->quantity
            === $quantity
    ) {
        return to_route(
            'configurator.show',
            $configuration,
        );
    }

    try {
        $updatedConfiguration =
            $editor->setComponent(
                configuration: $configuration,
                slot: $slot,
                variant: $variant,
                quantity: $quantity,
            );
    } catch (DomainException $exception) {
        throw ValidationException::withMessages([
            'variant_id' =>
                $exception->getMessage(),
        ]);
    }

    $validator->validate(
        $updatedConfiguration,
    );

    return to_route(
        'configurator.show',
        $configuration,
    )->with(
        'configuration_updated',
        true,
    );
}
}
