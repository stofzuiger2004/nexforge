<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Enums\InventoryMovementType;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\VariantPrice;
use App\Services\Inventory\InventoryLedgerService;
use App\Support\Money\DecimalMoney;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class AdminComponentCreator
{
    public function __construct(
        private readonly InventoryLedgerService $ledger,
        private readonly VariantSpecificationWriter $specificationWriter,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        array $data,
        UploadedFile $image,
        User $actor,
    ): InventoryItem {
        $disk = (string) config(
            'catalog.image_disk',
        );

        $directory = (string) config(
            'catalog.component_image_directory',
        );

        /*
         * Store first. If storage fails, no database
         * transaction starts.
         */
        $imagePath = $image->storePublicly(
            $directory,
            $disk,
        );

        try {
            return DB::transaction(
                function () use (
                    $data,
                    $image,
                    $imagePath,
                    $disk,
                    $actor,
                ): InventoryItem {
                    $active =
                        $data['status'] === 'active';

                    $product = Product::query()->create([
                        'brand_id' => $data[
                            'brand_id'
                        ],

                        'category_id' => $data[
                            'category_id'
                        ],

                        'name' => $data['name'],

                        'slug' => Str::slug(
                            $data['name']
                            .'-'
                            .$data['sku'],
                        ),

                        'description' => $data[
                            'description'
                        ],

                        'status' => $data['status'],
                        'is_configurable' => true,
                        'published_at' => $active
                            ? now()
                            : null,
                    ]);

                    $variant = $product
                        ->variants()
                        ->create([
                            'sku' => strtoupper(
                                $data['sku'],
                            ),

                            'name' => $data[
                                'variant_name'
                            ],

                            'manufacturer_part_number' =>
                                $data[
                                    'manufacturer_part_number'
                                ],

                            'barcode' => $data[
                                'barcode'
                            ],

                            'status' => $data['status'],
                            'is_default' => true,
                            'track_inventory' => true,
                        ]);

                    $product->images()->create([
                        'disk' => $disk,
                        'path' => $imagePath,

                        'alt_text' => $data[
                            'image_alt'
                        ] ?: $data['name'],

                        'is_primary' => true,
                        'sort_order' => 0,

                        'mime_type' => $image
                            ->getMimeType(),

                        'size_bytes' => $image
                            ->getSize(),
                    ]);

                    VariantPrice::query()->create([
                        'price_list_id' => $data[
                            'price_list_id'
                        ],

                        'product_variant_id' =>
                            $variant->id,

                        'amount_in_cents' =>
                            DecimalMoney::toCents(
                                $data['amount'],
                            ),

                        'compare_at_amount_in_cents' =>
                            empty(
                                $data[
                                    'compare_at_amount'
                                ]
                            )
                                ? null
                                : DecimalMoney::toCents(
                                    $data[
                                        'compare_at_amount'
                                    ],
                                ),

                        'lock_version' => 1,
                    ]);

                    /**
                     * @var array<string, mixed> $specifications
                     */
                    $specifications = $data['specifications'] ?? [];
                    foreach($specifications as $SpecificationKey => $values){
                        if($values === null || $values === '' || $values === []){
                            continue;
                        }
                        $this->specificationWriter->set(
                            $variant,(string) $SpecificationKey,$values
                        );
                    }

                    $inventoryItem = InventoryItem::query()
                        ->create([
                            'warehouse_id' => $data[
                                'warehouse_id'
                            ],

                            'product_variant_id' =>
                                $variant->id,

                            'quantity_on_hand' => 0,
                            'quantity_reserved' => 0,

                            'safety_stock' => $data[
                                'safety_stock'
                            ],

                            'reorder_point' => $data[
                                'reorder_point'
                            ],

                            'bin_location' => $data[
                                'bin_location'
                            ],

                            'is_active' => $active,

                            'lock_version' => 0,
                        ]);

                    if (
                        $data['opening_quantity'] > 0
                    ) {
                        $lockedItem = InventoryItem::query()
                            ->whereKey(
                                $inventoryItem->id,
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                        $this->ledger->applyToLockedItem(
                            inventoryItem: $lockedItem,

                            type: InventoryMovementType::Receipt,

                            onHandDelta: (int) $data[
                                'opening_quantity'
                            ],

                            reservedDelta: 0,

                            actor: $actor,

                            idempotencyKey:
                                'component-create:'
                                .$data[
                                    'creation_token'
                                ],

                            correlationId:
                                (string) Str::ulid(),

                            reason:
                                'Opening stock when component was created.',

                            metadata: [
                                'source' =>
                                    'admin_component_creation',

                                'sku' => $variant->sku,
                            ],
                        );
                    }

                    return $inventoryItem
                        ->refresh();
                },
                attempts: 3,
            );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete(
                $imagePath,
            );

            throw $exception;
        }
    }
}