<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasPublicUlid
{
    public static function bootHasPublicUlid(): void
    {
        static::creating(function (Model $model): void {
            $publicId = $model->getAttribute('public_id');

            if (! is_string($publicId) || $publicId === '') {
                $model->setAttribute(
                    'public_id',
                    (string) Str::ulid(),
                );
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
