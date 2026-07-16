<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(
        User $user,
    ): bool {
        return $user->can(
            'orders.viewAny',
        );
    }

    public function view(
        User $user,
        Order $order,
    ): bool {
        return $user->can(
            'orders.view',
        );
    }
}
