<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;

test('the application uses immutable dates by default', function (): void {
    expect(now())->toBeInstanceOf(CarbonImmutable::class);
});
