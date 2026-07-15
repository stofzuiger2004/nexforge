<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

final class OrderAccessService
{
    public function rememberGuestToken(Request $request, Order $order, string $guestToken): void
    {
        $request->session()->put($this->sessionKey($order), $guestToken);
    }

    public function assertCanAccess(Request $request, Order $order): void
    {
        $user = $request->user();
        if ($order->user_id !== null && $user !== null && (int) $user->getAuthIdentifier() == $order->user_id) {
            return;
        }

        if ($order->user_id !== null) {
            throw new AuthorizationException('You do not have access to this order.');
        }

        $guestToken = $request->session()->get($this->sessionKey($order));

        if (! is_string($guestToken) || $guestToken === '' || $order->guest_token_hash === null) {
            throw new AuthorizationException('You do not have access to this order.');
        }

        if (! hash_equals($order->guest_token_hash, hash('sha256', $guestToken))) {
            throw new AuthorizationException('You do not have access to this order.');
        }
    }

    private function sessionKey(Order $order): string
    {
        return sprintf('orders.access.%s', $order->public_id);
    }
}
