<?php

declare(strict_types=1);

return [
    'reservation_ttl_minutes' => max(1, (int) env('INVENTORY_RESERVATION_TTL_MINUTES', 15)),
    'demo_opening_quantity' => max(0, (int) env('INVENTORY_DEMO_OPENING_QUANTITY', 25)),
];
