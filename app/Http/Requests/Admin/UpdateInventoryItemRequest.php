<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'inventory.manage',
        ) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bin_location' => [
                'nullable',
                'string',
                'max:64',
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

            'is_active' => [
                'required',
                'boolean',
            ],

            'expected_lock_version' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}