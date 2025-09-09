<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:fees,code',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:fixed,percentage',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Fee name is required.',
            'code.required' => 'Fee code is required.',
            'code.unique' => 'Fee code already exists.',
            'amount.required' => 'Fee amount is required.',
            'amount.numeric' => 'Fee amount must be a number.',
            'amount.min' => 'Fee amount must be at least 0.',
            'type.required' => 'Fee type is required.',
            'type.in' => 'Fee type must be either fixed or percentage.',
        ];
    }
}