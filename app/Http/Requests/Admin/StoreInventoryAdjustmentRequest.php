<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use App\Enums\AdminInventoryAdjustmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.adjust') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(AdminInventoryAdjustmentType::class)
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:1000000'
            ],

            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500'
            ],

            'reference' => [
                'nullable',
                'string',
                'max:120'
            ],

            'expected_lock_version' => [
                'required',
                'integer',
                'min:0'
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:120'
            ]
        ];
    }

     public function after(): array{
        return [
            function (Validator $validator): void {
                $type = AdminInventoryAdjustmentType::tryFrom(
                    (string) $this->input('type')
                );

                $quantity = (int) $this->input(
                    'quantity',
                    0
                );

                if ($type !== null && $type !== AdminInventoryAdjustmentType::StockCount && $quantity < 1){
                    $validator->errors()->add(
                            'quantity',
                            'The quantity must be at least one for this adjustment type.'
                        );
                }
            },
        ];
    }

    public function adjustmentType(): AdminInventoryAdjustmentType{
        return AdminInventoryAdjustmentType::from(
            (string) $this->validated('type')
        );
    }
}
