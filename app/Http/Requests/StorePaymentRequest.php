<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'payment_type' => 'required|string|max:255',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'expected_amount' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,approved,rejected,confirmed',
            'rejection_reason' => 'nullable|string|max:500',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
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
            'payment_type.required' => 'Payment type is required.',
            'payment_type.max' => 'Payment type may not be greater than 255 characters.',
            'payment_method_id.exists' => 'Selected payment method does not exist.',
            'amount_paid.required' => 'Amount paid is required.',
            'amount_paid.numeric' => 'Amount paid must be a valid number.',
            'amount_paid.min' => 'Amount paid must be at least 0.01.',
            'expected_amount.numeric' => 'Expected amount must be a valid number.',
            'expected_amount.min' => 'Expected amount must be at least 0.',
            'reference_number.max' => 'Reference number may not be greater than 255 characters.',
            'receipt_image.max' => 'Receipt image path may not be greater than 255 characters.',
            'status.in' => 'Status must be one of: pending, approved, rejected, confirmed.',
            'rejection_reason.max' => 'Rejection reason may not be greater than 500 characters.',
            'payment_date.required' => 'Payment date is required.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'notes.max' => 'Notes may not be greater than 1000 characters.',
        ];
    }
}