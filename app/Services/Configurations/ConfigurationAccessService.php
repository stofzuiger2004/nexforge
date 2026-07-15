<?php

declare(strict_types=1);

namespace App\Services\Configurations;

use App\Models\Configuration;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

final class ConfigurationAccessService
{
    public function rememberGuestToken(
        Request $request,
        Configuration $configuration,
        string $guestToken,
    ): void {
        $request->session()->put(
            $this->sessionKey($configuration),
            $guestToken,
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function assertCanAccess(
        Request $request,
        Configuration $configuration,
    ): void {
        $user = $request->user();

        /*
         * Authenticated owner.
         */
        if (
            $configuration->user_id !== null
            && $user !== null
            && (int) $user->getAuthIdentifier()
                === $configuration->user_id
        ) {
            return;
        }

        /*
         * A configuration owned by another account may not
         * fall back to guest-token access.
         */
        if ($configuration->user_id !== null) {
            throw new AuthorizationException(
                'You do not have access to this configuration.',
            );
        }

        $guestToken = $request->session()->get(
            $this->sessionKey($configuration),
        );

        if (
            ! is_string($guestToken)
            || $guestToken === ''
            || $configuration->guest_token_hash === null
        ) {
            throw new AuthorizationException(
                'You do not have access to this configuration.',
            );
        }

        $providedHash = hash(
            'sha256',
            $guestToken,
        );

        if (
            ! hash_equals(
                $configuration->guest_token_hash,
                $providedHash,
            )
        ) {
            throw new AuthorizationException(
                'You do not have access to this configuration.',
            );
        }
    }

    private function sessionKey(
        Configuration $configuration,
    ): string {
        return sprintf(
            'configurator.access.%s',
            $configuration->public_id,
        );
    }
}