<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ComponentSlot;
use App\Enums\ProductStatus;
use App\Enums\SystemStatus;
use App\Enums\VariantStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\System;
use App\Models\SystemComponent;
use App\Models\SystemPrice;
use App\Models\VariantPrice;
use App\Services\Catalog\VariantSpecificationWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoStorefrontSeeder extends Seeder
{
    public function run(): void
    {
        $writer = app(VariantSpecificationWriter::class);

        DB::transaction(function () use ($writer): void {
            $brands = Brand::query()
                ->get()
                ->keyBy('slug');

            $categories = Category::query()
                ->get()
                ->keyBy('slug');

            $priceList = PriceList::query()
                ->where('code', 'retail-eur')
                ->firstOrFail();

            $variants = [];

            foreach ($this->products() as $key => $definition) {
                $product = Product::query()->updateOrCreate(
                    [
                        'slug' => $definition['slug'],
                    ],
                    [
                        'brand_id' => $brands[$definition['brand']]->id,
                        'category_id' => $categories[$definition['category']]->id,
                        'name' => $definition['name'],
                        'short_description' => $definition['description'],
                        'description' => null,
                        'status' => ProductStatus::Active,
                        'is_configurable' => true,
                        'published_at' => now(),
                    ],
                );

                $variant = ProductVariant::query()->updateOrCreate(
                    [
                        'sku' => $definition['sku'],
                    ],
                    [
                        'product_id' => $product->id,
                        'name' => $definition['variant_name'] ?? null,
                        'status' => VariantStatus::Active,
                        'is_default' => true,
                        'track_inventory' => true,
                    ],
                );

                VariantPrice::query()->updateOrCreate(
                    [
                        'price_list_id' => $priceList->id,
                        'product_variant_id' => $variant->id,
                    ],
                    [
                        'amount_in_cents' => $definition['price_in_cents'],
                        'compare_at_amount_in_cents' => null,
                    ],
                );

                foreach (
                    $definition['specifications'] as $specificationKey => $value
                ) {
                    $writer->set(
                        $variant,
                        $specificationKey,
                        $value,
                    );
                }

                $variants[$key] = $variant;
            }

            foreach ($this->systems() as $definition) {
                $system = System::query()->updateOrCreate(
                    [
                        'slug' => $definition['slug'],
                    ],
                    [
                        'sku' => $definition['sku'],
                        'name' => $definition['name'],
                        'short_description' => $definition['description'],
                        'description' => null,
                        'status' => SystemStatus::Active,
                        'is_featured' => true,
                        'is_configurable' => true,
                        'sort_order' => $definition['sort_order'],
                        'published_at' => now(),
                    ],
                );

                /*
                 * Rebuild this deterministic demo system's components
                 * so obsolete seeded components cannot remain.
                 */
                $system->components()->delete();

                $sortOrder = 10;

                foreach (
                    $definition['components'] as $slot => $variantKey
                ) {
                    SystemComponent::query()->create([
                        'system_id' => $system->id,
                        'product_variant_id' => $variants[$variantKey]->id,
                        'slot' => $slot,
                        'quantity' => 1,
                        'is_required' => true,
                        'is_replaceable' => true,
                        'sort_order' => $sortOrder,
                    ]);

                    $sortOrder += 10;
                }

                SystemPrice::query()->updateOrCreate(
                    [
                        'system_id' => $system->id,
                        'price_list_id' => $priceList->id,
                    ],
                    [
                        'amount_in_cents' => $definition['price_in_cents'],
                        'compare_at_amount_in_cents' => null,
                    ],
                );
            }
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function products(): array
    {
        return [
            'cpu-starter' => [
                'brand' => 'amd',
                'category' => 'processors',
                'name' => 'AMD Ryzen 5 7600',
                'slug' => 'amd-ryzen-5-7600',
                'sku' => 'CPU-AMD-R5-7600',
                'description' => 'Development sample processor.',
                'price_in_cents' => 19900,
                'specifications' => [
                    'socket' => 'am5',
                    'core_count' => 6,
                    'thread_count' => 12,
                    'tdp_watts' => 65,
                ],
            ],
            'cpu-pro' => [
                'brand' => 'amd',
                'category' => 'processors',
                'name' => 'AMD Ryzen 7 7800X3D',
                'slug' => 'amd-ryzen-7-7800x3d',
                'sku' => 'CPU-AMD-R7-7800X3D',
                'description' => 'Development sample processor.',
                'price_in_cents' => 39900,
                'specifications' => [
                    'socket' => 'am5',
                    'core_count' => 8,
                    'thread_count' => 16,
                    'tdp_watts' => 120,
                ],
            ],
            'cpu-ultimate' => [
                'brand' => 'amd',
                'category' => 'processors',
                'name' => 'AMD Ryzen 9 7950X3D',
                'slug' => 'amd-ryzen-9-7950x3d',
                'sku' => 'CPU-AMD-R9-7950X3D',
                'description' => 'Development sample processor.',
                'price_in_cents' => 59900,
                'specifications' => [
                    'socket' => 'am5',
                    'core_count' => 16,
                    'thread_count' => 32,
                    'tdp_watts' => 120,
                ],
            ],
            'motherboard' => [
                'brand' => 'asus',
                'category' => 'motherboards',
                'name' => 'ASUS B650 Gaming Motherboard',
                'slug' => 'asus-b650-gaming-motherboard',
                'sku' => 'MB-ASUS-B650-GAMING',
                'description' => 'Development sample motherboard.',
                'price_in_cents' => 19900,
                'specifications' => [
                    'socket' => 'am5',
                    'memory_type' => 'ddr5',
                    'motherboard_form_factor' => 'atx',
                ],
            ],
            'gpu-starter' => [
                'brand' => 'nvidia',
                'category' => 'graphics-cards',
                'name' => 'GeForce RTX 4060',
                'slug' => 'geforce-rtx-4060',
                'sku' => 'GPU-RTX-4060',
                'description' => 'Development sample graphics card.',
                'price_in_cents' => 32900,
                'specifications' => [
                    'gpu_length_mm' => 240,
                    'recommended_psu_watts' => 550,
                ],
            ],
            'gpu-pro' => [
                'brand' => 'nvidia',
                'category' => 'graphics-cards',
                'name' => 'GeForce RTX 4070 Super',
                'slug' => 'geforce-rtx-4070-super',
                'sku' => 'GPU-RTX-4070-SUPER',
                'description' => 'Development sample graphics card.',
                'price_in_cents' => 64900,
                'specifications' => [
                    'gpu_length_mm' => 300,
                    'recommended_psu_watts' => 650,
                ],
            ],
            'gpu-ultimate' => [
                'brand' => 'nvidia',
                'category' => 'graphics-cards',
                'name' => 'GeForce RTX 4080 Super',
                'slug' => 'geforce-rtx-4080-super',
                'sku' => 'GPU-RTX-4080-SUPER',
                'description' => 'Development sample graphics card.',
                'price_in_cents' => 109900,
                'specifications' => [
                    'gpu_length_mm' => 340,
                    'recommended_psu_watts' => 850,
                ],
            ],
            'memory-starter' => [
                'brand' => 'corsair',
                'category' => 'memory',
                'name' => 'Corsair 16 GB DDR5 Memory',
                'slug' => 'corsair-16gb-ddr5-memory',
                'sku' => 'RAM-CORSAIR-16-DDR5',
                'description' => 'Development sample memory kit.',
                'price_in_cents' => 7900,
                'specifications' => [
                    'memory_type' => 'ddr5',
                    'memory_capacity_gb' => 16,
                    'memory_speed_mts' => 6000,
                    'memory_module_count' => 2,
                ],
            ],
            'memory-pro' => [
                'brand' => 'corsair',
                'category' => 'memory',
                'name' => 'Corsair 32 GB DDR5 Memory',
                'slug' => 'corsair-32gb-ddr5-memory',
                'sku' => 'RAM-CORSAIR-32-DDR5',
                'description' => 'Development sample memory kit.',
                'price_in_cents' => 12900,
                'specifications' => [
                    'memory_type' => 'ddr5',
                    'memory_capacity_gb' => 32,
                    'memory_speed_mts' => 6000,
                    'memory_module_count' => 2,
                ],
            ],
            'memory-ultimate' => [
                'brand' => 'corsair',
                'category' => 'memory',
                'name' => 'Corsair 64 GB DDR5 Memory',
                'slug' => 'corsair-64gb-ddr5-memory',
                'sku' => 'RAM-CORSAIR-64-DDR5',
                'description' => 'Development sample memory kit.',
                'price_in_cents' => 23900,
                'specifications' => [
                    'memory_type' => 'ddr5',
                    'memory_capacity_gb' => 64,
                    'memory_speed_mts' => 6000,
                    'memory_module_count' => 2,
                ],
            ],
            'storage-starter' => [
                'brand' => 'samsung',
                'category' => 'storage',
                'name' => 'Samsung 1 TB NVMe SSD',
                'slug' => 'samsung-1tb-nvme-ssd',
                'sku' => 'SSD-SAMSUNG-NVME-1TB',
                'description' => 'Development sample storage.',
                'price_in_cents' => 8900,
                'specifications' => [
                    'storage_capacity_gb' => 1000,
                    'storage_interface' => 'nvme_pcie_4',
                ],
            ],
            'storage-pro' => [
                'brand' => 'samsung',
                'category' => 'storage',
                'name' => 'Samsung 2 TB NVMe SSD',
                'slug' => 'samsung-2tb-nvme-ssd',
                'sku' => 'SSD-SAMSUNG-NVME-2TB',
                'description' => 'Development sample storage.',
                'price_in_cents' => 15900,
                'specifications' => [
                    'storage_capacity_gb' => 2000,
                    'storage_interface' => 'nvme_pcie_4',
                ],
            ],
            'psu-starter' => [
                'brand' => 'seasonic',
                'category' => 'power-supplies',
                'name' => 'Seasonic 650 W Power Supply',
                'slug' => 'seasonic-650w-power-supply',
                'sku' => 'PSU-SEASONIC-650',
                'description' => 'Development sample power supply.',
                'price_in_cents' => 9900,
                'specifications' => [
                    'psu_wattage' => 650,
                    'psu_form_factor' => 'atx',
                ],
            ],
            'psu-pro' => [
                'brand' => 'seasonic',
                'category' => 'power-supplies',
                'name' => 'Seasonic 750 W Power Supply',
                'slug' => 'seasonic-750w-power-supply',
                'sku' => 'PSU-SEASONIC-750',
                'description' => 'Development sample power supply.',
                'price_in_cents' => 12900,
                'specifications' => [
                    'psu_wattage' => 750,
                    'psu_form_factor' => 'atx',
                ],
            ],
            'psu-ultimate' => [
                'brand' => 'seasonic',
                'category' => 'power-supplies',
                'name' => 'Seasonic 1000 W Power Supply',
                'slug' => 'seasonic-1000w-power-supply',
                'sku' => 'PSU-SEASONIC-1000',
                'description' => 'Development sample power supply.',
                'price_in_cents' => 19900,
                'specifications' => [
                    'psu_wattage' => 1000,
                    'psu_form_factor' => 'atx',
                ],
            ],
            'case' => [
                'brand' => 'fractal-design',
                'category' => 'cases',
                'name' => 'Fractal Design Airflow Case',
                'slug' => 'fractal-design-airflow-case',
                'sku' => 'CASE-FRACTAL-AIRFLOW',
                'description' => 'Development sample PC case.',
                'price_in_cents' => 11900,
                'specifications' => [
                    'supported_motherboard_form_factors' => [
                        'atx',
                        'micro_atx',
                        'mini_itx',
                    ],
                    'case_max_gpu_length_mm' => 380,
                    'case_max_cooler_height_mm' => 170,
                ],
            ],
            'cooler' => [
                'brand' => 'noctua',
                'category' => 'cpu-coolers',
                'name' => 'Noctua Tower CPU Cooler',
                'slug' => 'noctua-tower-cpu-cooler',
                'sku' => 'COOLER-NOCTUA-TOWER',
                'description' => 'Development sample CPU cooler.',
                'price_in_cents' => 6900,
                'specifications' => [
                    'cooler_sockets' => [
                        'am5',
                        'lga1700',
                    ],
                    'cooler_height_mm' => 158,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function systems(): array
    {
        return [
            [
                'sku' => 'SYSTEM-1080P-STARTER',
                'name' => '1080p Starter',
                'slug' => '1080p-starter',
                'description' => 'Smooth competitive gaming at 1080p.',
                'price_in_cents' => 99900,
                'sort_order' => 10,
                'components' => [
                    ComponentSlot::Cpu->value => 'cpu-starter',
                    ComponentSlot::Motherboard->value => 'motherboard',
                    ComponentSlot::GraphicsCard->value => 'gpu-starter',
                    ComponentSlot::Memory->value => 'memory-starter',
                    ComponentSlot::PrimaryStorage->value => 'storage-starter',
                    ComponentSlot::PowerSupply->value => 'psu-starter',
                    ComponentSlot::Case->value => 'case',
                    ComponentSlot::CpuCooler->value => 'cooler',
                ],
            ],
            [
                'sku' => 'SYSTEM-1440P-PRO',
                'name' => '1440p Pro',
                'slug' => '1440p-pro',
                'description' => 'High-refresh gaming at 1440p.',
                'price_in_cents' => 169900,
                'sort_order' => 20,
                'components' => [
                    ComponentSlot::Cpu->value => 'cpu-pro',
                    ComponentSlot::Motherboard->value => 'motherboard',
                    ComponentSlot::GraphicsCard->value => 'gpu-pro',
                    ComponentSlot::Memory->value => 'memory-pro',
                    ComponentSlot::PrimaryStorage->value => 'storage-pro',
                    ComponentSlot::PowerSupply->value => 'psu-pro',
                    ComponentSlot::Case->value => 'case',
                    ComponentSlot::CpuCooler->value => 'cooler',
                ],
            ],
            [
                'sku' => 'SYSTEM-4K-ULTIMATE',
                'name' => '4K Ultimate',
                'slug' => '4k-ultimate',
                'description' => 'Premium performance for high-detail 4K gaming.',
                'price_in_cents' => 279900,
                'sort_order' => 30,
                'components' => [
                    ComponentSlot::Cpu->value => 'cpu-ultimate',
                    ComponentSlot::Motherboard->value => 'motherboard',
                    ComponentSlot::GraphicsCard->value => 'gpu-ultimate',
                    ComponentSlot::Memory->value => 'memory-ultimate',
                    ComponentSlot::PrimaryStorage->value => 'storage-pro',
                    ComponentSlot::PowerSupply->value => 'psu-ultimate',
                    ComponentSlot::Case->value => 'case',
                    ComponentSlot::CpuCooler->value => 'cooler',
                ],
            ],
        ];
    }
}
