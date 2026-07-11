<?php

namespace Database\Factories;

use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<System>
 */
class SystemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            '1080p Starter',
            '1440p Pro',
            '4K Ultimate',
            'Competitive Series',
            'Creator Gaming Pro',
        ]);
        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->sentence(),
            'processor' => fake()->randomElement([
                'AMD Ryzen 5 7600',
                'AMD Ryzen 7 7800X3D',
                'Intel Core i5',
                'Intel Core i7',
            ]),
            'graphics_card' => fake()->randomElement([
                'GeForce RTX 4060',
                'GeForce RTX 4070 Super',
                'GeForce RTX 4080 Super',
                'Radeon RX 7800 XT',
            ]),
            'memory' => fake()->randomElement([
                '16 GB DDR5',
                '32 GB DDR5',
                '64 GB DDR5',
            ]),
            'storage' => fake()->randomElement([
                '1 TB NVMe SSD',
                '2 TB NVMe SSD',
            ]),
            'price_in_cents' => fake()->numberBetween(89900, 299900),
            'is_featured' => fake()->boolean(40),
            'is_active' => true,
            'image_path' => null,
        ];
    }
}
