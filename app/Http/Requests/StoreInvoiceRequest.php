<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
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
            'invoice_number' => 'nullable|string|max:255|unique:invoices,invoice_number',
            'customer_id' => 'required|exists:users,id',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'product_image' => 'nullable|string|max:255',
            'payment_term_id' => 'nullable|exists:payment_terms,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'fee_id' => 'nullable|exists:fees,id',
            'discount_id' => 'nullable|exists:discounts,id',
            'shipping_address' => 'nullable|string',
            'issue_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'active' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Customer selection is required.',
            'customer_id.exists' => 'Selected customer does not exist.',
            'product_name.required' => 'Product name is required.',
            'product_name.max' => 'Product name may not be greater than 255 characters.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price must be at least 0.',
            'payment_term_id.exists' => 'Selected payment term does not exist.',
            'tax_id.exists' => 'Selected tax does not exist.',
            'fee_id.exists' => 'Selected fee does not exist.',
            'discount_id.exists' => 'Selected discount does not exist.',
            'issue_date.date' => 'Issue date must be a valid date.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be after or equal to issue date.',
            'status.in' => 'Status must be one of: draft, sent, paid, overdue, cancelled.',
            'invoice_number.unique' => 'Invoice number already exists.',
        ];
    }
}