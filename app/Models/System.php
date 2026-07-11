<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    /** @use HasFactory<\Database\Factories\SystemFactory> */
    use HasFactory;

    protected $fillable=[
        'name',
        'slug',
        'description',
        'processor',
        'graphics_card',
        'memory',
        'storage',
        'price_in_cents',
        'is_featured',
        'is_active',
        'image_path'
    ];

    protected function casts(): array{
        return [
            'price_in_cents'=>'integer',
            'is_featured'=>'boolean',
            'is_active'=>'boolean'
        ];
    }
}
