<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\SpecificationDataType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Specification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBrands();
            $categories = $this->seedCategories();
            $specifications = $this->seedSpecifications();

            $this->seedSpecificationOptions($specifications);
            $this->assignSpecifications(
                $categories,
                $specifications,
            );
        });
    }

    private function seedBrands(): void
    {
        $brands = [
            ['name' => 'AMD', 'slug' => 'amd'],
            ['name' => 'Intel', 'slug' => 'intel'],
            ['name' => 'NVIDIA', 'slug' => 'nvidia'],
            ['name' => 'ASUS', 'slug' => 'asus'],
            ['name' => 'MSI', 'slug' => 'msi'],
            ['name' => 'Gigabyte', 'slug' => 'gigabyte'],
            ['name' => 'Corsair', 'slug' => 'corsair'],
            ['name' => 'Samsung', 'slug' => 'samsung'],
            ['name' => 'Seasonic', 'slug' => 'seasonic'],
            [
                'name' => 'Fractal Design',
                'slug' => 'fractal-design',
            ],
            ['name' => 'Noctua', 'slug' => 'noctua'],
        ];

        foreach ($brands as $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $root = Category::query()->updateOrCreate(
            ['slug' => 'components'],
            [
                'parent_id' => null,
                'name' => 'Components',
                'type' => CategoryType::Component,
                'sort_order' => 10,
                'is_active' => true,
            ],
        );

        $definitions = [
            'processors' => [
                'name' => 'Processors',
                'sort_order' => 10,
            ],
            'motherboards' => [
                'name' => 'Motherboards',
                'sort_order' => 20,
            ],
            'graphics-cards' => [
                'name' => 'Graphics Cards',
                'sort_order' => 30,
            ],
            'memory' => [
                'name' => 'Memory',
                'sort_order' => 40,
            ],
            'storage' => [
                'name' => 'Storage',
                'sort_order' => 50,
            ],
            'power-supplies' => [
                'name' => 'Power Supplies',
                'sort_order' => 60,
            ],
            'cases' => [
                'name' => 'Cases',
                'sort_order' => 70,
            ],
            'cpu-coolers' => [
                'name' => 'CPU Coolers',
                'sort_order' => 80,
            ],
            'case-fans' => [
                'name' => 'Case Fans',
                'sort_order' => 90,
            ],
            'operating-systems' => [
                'name' => 'Operating Systems',
                'sort_order' => 100,
            ],
        ];

        $categories = [];

        foreach ($definitions as $slug => $definition) {
            $categories[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => $root->id,
                    'name' => $definition['name'],
                    'type' => CategoryType::Component,
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        return $categories;
    }

    /**
     * @return array<string, Specification>
     */
    private function seedSpecifications(): array
    {
        $definitions = [
            'socket' => [
                'name' => 'Socket',
                'data_type' => SpecificationDataType::Option,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'memory_type' => [
                'name' => 'Memory Type',
                'data_type' => SpecificationDataType::Option,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'motherboard_form_factor' => [
                'name' => 'Motherboard Form Factor',
                'data_type' => SpecificationDataType::Option,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'supported_motherboard_form_factors' => [
                'name' => 'Supported Motherboard Form Factors',
                'data_type' => SpecificationDataType::MultiOption,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'core_count' => [
                'name' => 'Core Count',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'cores',
                'filterable' => true,
                'compatibility' => false,
            ],
            'thread_count' => [
                'name' => 'Thread Count',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'threads',
                'filterable' => true,
                'compatibility' => false,
            ],
            'tdp_watts' => [
                'name' => 'Thermal Design Power',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'W',
                'filterable' => true,
                'compatibility' => true,
            ],
            'gpu_length_mm' => [
                'name' => 'Graphics Card Length',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'mm',
                'filterable' => true,
                'compatibility' => true,
            ],
            'recommended_psu_watts' => [
                'name' => 'Recommended PSU Wattage',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'W',
                'filterable' => true,
                'compatibility' => true,
            ],
            'memory_capacity_gb' => [
                'name' => 'Memory Capacity',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'GB',
                'filterable' => true,
                'compatibility' => false,
            ],
            'memory_speed_mts' => [
                'name' => 'Memory Speed',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'MT/s',
                'filterable' => true,
                'compatibility' => false,
            ],
            'memory_module_count' => [
                'name' => 'Memory Module Count',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'modules',
                'filterable' => false,
                'compatibility' => false,
            ],
            'storage_capacity_gb' => [
                'name' => 'Storage Capacity',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'GB',
                'filterable' => true,
                'compatibility' => false,
            ],
            'storage_interface' => [
                'name' => 'Storage Interface',
                'data_type' => SpecificationDataType::Option,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'psu_wattage' => [
                'name' => 'PSU Wattage',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'W',
                'filterable' => true,
                'compatibility' => true,
            ],
            'psu_form_factor' => [
                'name' => 'PSU Form Factor',
                'data_type' => SpecificationDataType::Option,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
            'case_max_gpu_length_mm' => [
                'name' => 'Maximum GPU Length',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'mm',
                'filterable' => true,
                'compatibility' => true,
            ],
            'case_max_cooler_height_mm' => [
                'name' => 'Maximum CPU Cooler Height',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'mm',
                'filterable' => true,
                'compatibility' => true,
            ],
            'cooler_height_mm' => [
                'name' => 'CPU Cooler Height',
                'data_type' => SpecificationDataType::Integer,
                'unit' => 'mm',
                'filterable' => true,
                'compatibility' => true,
            ],
            'cooler_sockets' => [
                'name' => 'Supported CPU Sockets',
                'data_type' => SpecificationDataType::MultiOption,
                'unit' => null,
                'filterable' => true,
                'compatibility' => true,
            ],
        ];

        $specifications = [];
        $sortOrder = 10;

        foreach ($definitions as $key => $definition) {
            $specifications[$key] =
                Specification::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'name' => $definition['name'],
                        'data_type' => $definition['data_type'],
                        'unit' => $definition['unit'],
                        'is_filterable' => $definition['filterable'],
                        'is_comparable' => true,
                        'is_compatibility_key' => $definition['compatibility'],
                        'is_active' => true,
                        'sort_order' => $sortOrder,
                    ],
                );

            $sortOrder += 10;
        }

        return $specifications;
    }

    /**
     * @param  array<string, Specification>  $specifications
     */
    private function seedSpecificationOptions(
        array $specifications,
    ): void {
        $options = [
            'socket' => [
                'am5' => 'AM5',
                'lga1700' => 'LGA 1700',
                'lga1851' => 'LGA 1851',
            ],
            'memory_type' => [
                'ddr4' => 'DDR4',
                'ddr5' => 'DDR5',
            ],
            'motherboard_form_factor' => [
                'atx' => 'ATX',
                'micro_atx' => 'Micro-ATX',
                'mini_itx' => 'Mini-ITX',
            ],
            'supported_motherboard_form_factors' => [
                'atx' => 'ATX',
                'micro_atx' => 'Micro-ATX',
                'mini_itx' => 'Mini-ITX',
            ],
            'storage_interface' => [
                'nvme_pcie_4' => 'NVMe PCIe 4.0',
                'nvme_pcie_5' => 'NVMe PCIe 5.0',
                'sata' => 'SATA',
            ],
            'psu_form_factor' => [
                'atx' => 'ATX',
                'sfx' => 'SFX',
            ],
            'cooler_sockets' => [
                'am5' => 'AM5',
                'lga1700' => 'LGA 1700',
                'lga1851' => 'LGA 1851',
            ],
        ];

        foreach ($options as $specificationKey => $values) {
            $sortOrder = 10;

            foreach ($values as $value => $label) {
                $specifications[$specificationKey]
                    ->options()
                    ->updateOrCreate(
                        ['value' => $value],
                        [
                            'label' => $label,
                            'sort_order' => $sortOrder,
                            'is_active' => true,
                        ],
                    );

                $sortOrder += 10;
            }
        }
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<string, Specification>  $specifications
     */
    private function assignSpecifications(
        array $categories,
        array $specifications,
    ): void {
        $assignments = [
            'processors' => [
                'socket' => true,
                'core_count' => true,
                'thread_count' => true,
                'tdp_watts' => true,
            ],
            'motherboards' => [
                'socket' => true,
                'memory_type' => true,
                'motherboard_form_factor' => true,
            ],
            'graphics-cards' => [
                'gpu_length_mm' => true,
                'recommended_psu_watts' => true,
            ],
            'memory' => [
                'memory_type' => true,
                'memory_capacity_gb' => true,
                'memory_speed_mts' => true,
                'memory_module_count' => true,
            ],
            'storage' => [
                'storage_capacity_gb' => true,
                'storage_interface' => true,
            ],
            'power-supplies' => [
                'psu_wattage' => true,
                'psu_form_factor' => true,
            ],
            'cases' => [
                'supported_motherboard_form_factors' => true,
                'case_max_gpu_length_mm' => true,
                'case_max_cooler_height_mm' => true,
            ],
            'cpu-coolers' => [
                'cooler_sockets' => true,
                'cooler_height_mm' => true,
            ],
        ];

        foreach ($assignments as $categorySlug => $items) {
            $sortOrder = 10;

            foreach ($items as $specificationKey => $required) {
                $categories[$categorySlug]
                    ->specifications()
                    ->syncWithoutDetaching([
                        $specifications[$specificationKey]->id => [
                            'is_required' => $required,
                            'sort_order' => $sortOrder,
                        ],
                    ]);

                $sortOrder += 10;
            }
        }
    }
}
