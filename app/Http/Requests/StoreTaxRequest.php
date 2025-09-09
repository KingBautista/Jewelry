<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:taxes,code',
            'rate' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tax name is required.',
            'code.required' => 'Tax code is required.',
            'code.unique' => 'Tax code already exists.',
            'rate.required' => 'Tax rate is required.',
            'rate.numeric' => 'Tax rate must be a number.',
            'rate.min' => 'Tax rate must be at least 0.',
            'rate.max' => 'Tax rate cannot exceed 100%.',
        ];
    }
}