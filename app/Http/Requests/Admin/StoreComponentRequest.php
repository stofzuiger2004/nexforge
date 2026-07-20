<?php

declare(strict_types=1);
namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;
use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog.manage') ?? false;
    }

    protected function prepareForValidation(): void{
        $specifications = $this->input('specifications');

        if($specifications === null || $specifications === ''){
            $this->merge(['specifications'=>[]]);
        }
    }
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $moneyPattern = '/^\d{1,9}(?:[.,]\d{1,2})?$/';
         return [
            'name' => [
                'required',
                'string',
                'max:180',
            ],

            'variant_name' => [
                'nullable',
                'string',
                'max:180',
            ],

            'sku' => [
                'required',
                'string',
                'max:80',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique(
                    'product_variants',
                    'sku',
                ),
            ],

            'manufacturer_part_number' => [
                'nullable',
                'string',
                'max:120',
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique(
                    'product_variants',
                    'barcode',
                ),
            ],

            'brand_id' => [
                'required',
                'integer',
                Rule::exists('brands', 'id')
                    ->where(
                        'is_active',
                        true,
                    ),
            ],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(
                        'is_active',
                        true,
                    ),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'active',
                ]),
            ],

            'image' => [
                'required',
                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max('5mb')
                    ->dimensions(
                        Rule::dimensions()
                            ->minWidth(400)
                            ->minHeight(400)
                            ->maxWidth(6000)
                            ->maxHeight(6000),
                    ),
            ],

            'image_alt' => [
                'nullable',
                'string',
                'max:255',
            ],

            'price_list_id' => [
                'required',
                'integer',
                Rule::exists(
                    'price_lists',
                    'id',
                )->where(
                    'is_active',
                    true,
                ),
            ],

            'amount' => [
                'required',
                'string',
                "regex:{$moneyPattern}",
            ],

            'compare_at_amount' => [
                'nullable',
                'string',
                "regex:{$moneyPattern}",
            ],

            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists(
                    'warehouses',
                    'id',
                )->where(
                    'is_active',
                    true,
                ),
            ],

            'opening_quantity' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'safety_stock' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'reorder_point' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'bin_location' => [
                'nullable',
                'string',
                'max:64',
            ],

            'specifications' => [
                'present',
                'array',
            ],

            'creation_token' => [
                'required',
                'string',
                'max:120',
            ],
        ];
    }
    public function after(): array{
        return [
            function(Validator $validator): void{
                $safetyStock = (int) $this->input('safety_stock',0);
                $reorderPoint = $this->input('reorder_point');

                if($reorderPoint !== null && $reorderPoint !== '' && (int) $reorderPoint < $safetyStock){
                    $validator->errors()->add('reorder_point','The reorder point should be greater than or equal to the safety stock.');
                }

                $submittedSpecifications = $this->input('specifications',[]);

                if(! is_array($submittedSpecifications)){
                    return;
                }

                $category = Category::query()->with([
                    'specifications' => static fn ($query) =>
                        $query->where('specifications.is_active',true)
                ])->find((int) $this->input('category_id'));

                if($category === null){
                    return;
                }

                $allowedSpecificationKeys = $category->specifications->pluck('key')->all();

                foreach(array_keys($submittedSpecifications) as $submittedKey){
                    if(!in_array($submittedKey,$allowedSpecificationKeys,true)){
                        $validator->errors()->add('specifications.'.$submittedKey,'This specification does not belong to the selected component type.');
                    }
                }

                foreach($category->specifications as $specification){
                    if(! (bool) $specification->pivot->is_required){
                        continue;
                    }

                    $value = $submittedSpecifications[$specification->key] ?? null;

                    if($this->specificationValueIsEmpty($value)){
                        $validator->errors()->add('specifications.'.$specification->key,sprintf('The %s specification is required.',$specification->name));
                    }
            }
                }

                
        ];
    }
    private function specificationValueIsEmpty(
            mixed $value,
        ): bool {
            if ($value === null) {
                return true;
            }

            if (
                is_string($value)
                && trim($value) === ''
            ) {
                return true;
            }

            if (
                is_array($value)
                && $value === []
            ) {
                return true;
            }

            /*
            * false and 0 are valid specification values.
            */
            return false;
}
}
