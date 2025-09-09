<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => 'required|string|max:255',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'required|string|max:50',
            'description' => 'nullable|string',
            'qr_code_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'bank_name.required' => 'Bank name is required.',
            'account_number.required' => 'Account number is required.',
            'qr_code_image.image' => 'QR code must be an image.',
            'qr_code_image.mimes' => 'QR code must be a file of type: jpeg, png, jpg, gif.',
            'qr_code_image.max' => 'QR code may not be greater than 2MB.',
        ];
    }
}