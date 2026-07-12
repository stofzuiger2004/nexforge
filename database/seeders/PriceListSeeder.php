<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PriceList;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PriceList::query()->updateOrCreate(
            ['code' => 'retail-eur'],
            [
                'name' => 'EUR Retail',
                'currency' => 'EUR',
                'prices_include_tax' => true,
                'is_default' => true,
                'is_active' => true,
                'starts_at' => null,
                'ends_at' => null,
            ],
        );
    }
}
