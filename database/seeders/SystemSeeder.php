<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\System;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        System::query()->create([
            'name' => '1080p Starter',
            'slug' => '1080p-starter',
            'description' => 'Smooth competitive gaming at 1080p.',
            'processor' => 'AMD Ryzen 5 7600',
            'graphics_card' => 'GeForce RTX 4060',
            'memory' => '16 GB DDR5',
            'storage' => '1 TB NVMe SSD',
            'price_in_cents' => 99900,
            'is_featured' => true,
            'is_active' => true,
        ]);

        System::query()->create([
            'name' => '1440p Pro',
            'slug' => '1440p-pro',
            'description' => 'High-refresh gaming at 1440p.',
            'processor' => 'AMD Ryzen 7 7800X3D',
            'graphics_card' => 'GeForce RTX 4070 Super',
            'memory' => '32 GB DDR5',
            'storage' => '2 TB NVMe SSD',
            'price_in_cents' => 169900,
            'is_featured' => true,
            'is_active' => true,
        ]);

        System::query()->create([
            'name' => '4K Ultimate',
            'slug' => '4k-ultimate',
            'description' => 'Premium performance for high-detail 4K gaming.',
            'processor' => 'AMD Ryzen 9 7950X3D',
            'graphics_card' => 'GeForce RTX 4080 Super',
            'memory' => '64 GB DDR5',
            'storage' => '2 TB NVMe SSD',
            'price_in_cents' => 279900,
            'is_featured' => true,
            'is_active' => true,
        ]);

        System::factory()
            ->count(8)
            ->create();
    }
}
