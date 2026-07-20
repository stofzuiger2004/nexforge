<?php

declare(strict_types=1);
namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemDefaultComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('catalog.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants','id')->whereNull('deleted_at')
            ],
            'quantity'=>[
                'required',
                'integer',
                'min:1',
                'max:100'
            ],
            'is_required'=>[
                'required',
                'boolean'
            ],
            'is_replaceable'=>[
                'required',
                'boolean'
            ],
        ];
    }
}
