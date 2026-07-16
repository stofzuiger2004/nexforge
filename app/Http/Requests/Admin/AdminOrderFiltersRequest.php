<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\OrderFulfillmentStatus;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminOrderFiltersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::enum(
                    OrderStatus::class,
                ),
            ],

            'payment_status' => [
                'nullable',
                Rule::enum(
                    OrderPaymentStatus::class,
                ),
            ],

            'fulfillment_status' => [
                'nullable',
                Rule::enum(
                    OrderFulfillmentStatus::class,
                ),
            ],

            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'newest',
                    'oldest',
                    'total_high',
                    'total_low',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    15,
                    25,
                    50,
                    100,
                ]),
            ],
        ];
    }

    /**
     * @return array{
     *     search: string|null,
     *     status: string|null,
     *     payment_status: string|null,
     *     fulfillment_status: string|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     sort: string,
     *     per_page: int
     * }
     */
    public function filters(): array
    {
        $data = $this->validated();

        $search = trim(
            (string) (
                $data['search']
                ?? ''
            ),
        );

        return [
            'search' => $search === ''
                    ? null
                    : $search,

            'status' => $data['status']
                ?? null,

            'payment_status' => $data['payment_status']
                ?? null,

            'fulfillment_status' => $data['fulfillment_status']
                ?? null,

            'date_from' => $data['date_from']
                ?? null,

            'date_to' => $data['date_to']
                ?? null,

            'sort' => $data['sort']
                ?? 'newest',

            'per_page' => (int) (
                $data['per_page']
                ?? 25
            ),
        ];
    }
}
