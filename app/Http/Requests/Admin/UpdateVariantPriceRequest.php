<?php
declare(strict_types=1);
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('prices.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $moneyPattern = '/^\d{1,9}(?:[.,]\d{1,2})?$/';
        return [
            'amount' => [
                'required',
                'string',
                "regex:{$moneyPattern}"
            ],
            'compare_at_amount' => [
                'nullable',
                'string',
                "regex:{$moneyPattern}"
            ],
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:500'
            ],
            'expected_lock_version' => [
                'required',
                'integer',
                'min:1'
            ]
        ];
    }
}
