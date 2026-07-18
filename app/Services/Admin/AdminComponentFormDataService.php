<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceList;
use App\Models\Warehouse;
use App\Models\Specification;
use App\Models\SpecificationOption;

final class AdminComponentFormDataService
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ])
                ->map(
                    static fn (
                        Brand $brand,
                    ): array => [
                        'value' => $brand->id,
                        'label' => $brand->name,
                    ],
                )
                ->values()
                ->all(),

            'categories' => Category::query()
                ->where('is_active', true)
                ->with([
                    'specifications' => fn (
                        $query,
                    ) => $query
                        ->with('options')
                        ->orderByPivot(
                            'sort_order',
                        ),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(
                    fn (
                        Category $category,
                    ): array => [
                        'value' => $category->id,
                        'label' => $category->name,
                        'slug' => $category->slug,

                        'specifications' => $category
                            ->specifications
                            ->map(
                                fn (
                                    $specification,
                                ): array => [
                                    'id' => $specification
                                        ->id,

                                    'key' => $specification
                                        ->key,

                                    'label' => $specification
                                        ->name,

                                    'data_type' => $specification
                                        ->data_type
                                        ->value,

                                    'unit' => $specification
                                        ->unit,

                                    'required' => (bool) $specification
                                        ->pivot
                                        ->is_required,

                                    'options' => $specification->options->map(
                                        static fn (SpecificationOption $option): array => [
                                            'value'=>$option->value,
                                            'label'=>$option->label
                                        ]
                                    )->values()->all()
                                ],
                            )
                            ->values()
                            ->all(),
                    ],
                )
                ->values()
                ->all(),

            'warehouses' => Warehouse::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderBy('name')
                ->get([
                    'id',
                    'code',
                    'name',
                ])
                ->map(
                    static fn (
                        Warehouse $warehouse,
                    ): array => [
                        'value' => $warehouse->id,
                        'label' => $warehouse->name
                            .' ('
                            .$warehouse->code
                            .')',
                    ],
                )
                ->values()
                ->all(),

            'price_lists' => PriceList::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'currency',
                    'is_default',
                ])
                ->map(
                    static fn (
                        PriceList $priceList,
                    ): array => [
                        'value' => $priceList->id,
                        'label' => $priceList->name,
                        'currency' => $priceList
                            ->currency,
                        'is_default' => $priceList
                            ->is_default,
                    ],
                )
                ->values()
                ->all(),

            'statuses' => [
                [
                    'value' => 'draft',
                    'label' => 'Draft',
                ],
                [
                    'value' => 'active',
                    'label' => 'Active',
                ],
            ],
        ];
    }
}