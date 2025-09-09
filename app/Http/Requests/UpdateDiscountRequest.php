<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $discountId = $this->route('id');
        
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('discounts', 'code')->ignore($discountId)
            ],
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:fixed,percentage',
            'description' => 'nullable|string',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'usage_limit' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Discount name is required.',
            'code.required' => 'Discount code is required.',
            'code.unique' => 'Discount code already exists.',
            'amount.required' => 'Discount amount is required.',
            'amount.numeric' => 'Discount amount must be a number.',
            'amount.min' => 'Discount amount must be at least 0.',
            'type.required' => 'Discount type is required.',
            'type.in' => 'Discount type must be either fixed or percentage.',
            'valid_until.after' => 'Valid until date must be after valid from date.',
            'usage_limit.integer' => 'Usage limit must be an integer.',
            'usage_limit.min' => 'Usage limit must be at least 1.',
        ];
    }
}