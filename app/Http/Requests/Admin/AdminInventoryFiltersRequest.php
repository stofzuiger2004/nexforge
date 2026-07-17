<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AdminInventoryFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.view') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:150'
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouses', 'id')
            ],

            'stock_state' => [
                'nullable',
                Rule::in([
                    'in_stock',
                    'low_stock',
                    'out_of_stock',
                    'inactive'
                ])
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'sku_asc',
                    'available_asc',
                    'available_desc',
                    'updated_desc'
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    25,
                    50,
                    100
                ])
            ]
        ];
    }
    /**
     * @return array{
     *     search: string|null,
     *     warehouse_id: int|null,
     *     stock_state: string|null,
     *     sort: string,
     *     per_page: int
     * }
     */
    public function filters(): array{
        $validated = $this->validated();

        $search = isset($validated['search'])
            ? trim((string) $validated['search'])
            : null;

        return [
            'search' => $search === ''
                ? null
                : $search,

            'warehouse_id' => isset(
                $validated['warehouse_id'],
            )
                ? (int) $validated['warehouse_id']
                : null,

            'stock_state' => isset(
                $validated['stock_state'],
            )
                ? (string) $validated['stock_state']
                : null,

            'sort' => isset($validated['sort'])
                ? (string) $validated['sort']
                : 'sku_asc',

            'per_page' => isset(
                $validated['per_page'],
            )
                ? (int) $validated['per_page']
                : 25,
        ];
    }
}
