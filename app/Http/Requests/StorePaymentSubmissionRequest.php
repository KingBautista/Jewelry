<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentSubmissionRequest extends FormRequest
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
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:users,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'expected_amount' => 'required|numeric|min:0.01',
            'reference_number' => 'required|string|max:255',
            'receipt_images' => 'nullable|array',
            'receipt_images.*' => 'string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Invoice selection is required.',
            'invoice_id.exists' => 'Selected invoice does not exist.',
            'customer_id.required' => 'Customer selection is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'amount_paid.required' => 'Amount paid is required.',
            'amount_paid.numeric' => 'Amount paid must be a valid number.',
            'amount_paid.min' => 'Amount paid must be at least 0.01.',
            'expected_amount.required' => 'Expected amount is required.',
            'expected_amount.numeric' => 'Expected amount must be a valid number.',
            'expected_amount.min' => 'Expected amount must be at least 0.01.',
            'reference_number.required' => 'Reference number is required.',
            'reference_number.max' => 'Reference number may not be greater than 255 characters.',
            'receipt_images.array' => 'Receipt images must be an array.',
            'receipt_images.*.string' => 'Each receipt image must be a string.',
            'receipt_images.*.max' => 'Each receipt image path may not be greater than 255 characters.',
        ];
    }
}