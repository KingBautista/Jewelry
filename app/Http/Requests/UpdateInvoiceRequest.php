<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert string "true"/"false" to boolean for active field
        if ($this->has('active')) {
            $active = $this->input('active');
            if (is_string($active)) {
                $this->merge([
                    'active' => filter_var($active, FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $invoiceId = $this->route('id');
        
        return [
            'invoice_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')->ignore($invoiceId)
            ],
            'customer_id' => 'required|exists:users,id',
            'products' => 'required|array|min:1',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.description' => 'nullable|string',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.product_images' => 'nullable|array',
            'products.*.product_images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
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
            'products.required' => 'At least one product is required.',
            'products.array' => 'Products must be an array.',
            'products.min' => 'At least one product is required.',
            'products.*.product_name.required' => 'Product name is required for each product.',
            'products.*.product_name.max' => 'Product name may not be greater than 255 characters.',
            'products.*.price.required' => 'Price is required for each product.',
            'products.*.price.numeric' => 'Price must be a valid number.',
            'products.*.price.min' => 'Price must be at least 0.',
            'payment_term_id.exists' => 'Selected payment term does not exist.',
            'tax_id.exists' => 'Selected tax does not exist.',
            'fee_id.exists' => 'Selected fee does not exist.',
            'discount_id.exists' => 'Selected discount does not exist.',
            'issue_date.date' => 'Issue date must be a valid date.',
            'due_date.date' => 'Due date must be a valid date.',
            'due_date.after_or_equal' => 'Due date must be after or equal to issue date.',
            'status.in' => 'Status must be one of: draft, sent, paid, overdue, cancelled.',
            'invoice_number.unique' => 'Invoice number already exists.',
            'products.*.product_images.*.file' => 'Each product image must be a valid file.',
            'products.*.product_images.*.image' => 'Each product image must be an image file.',
            'products.*.product_images.*.mimes' => 'Product images must be in JPEG, PNG, JPG, GIF, or WebP format.',
            'products.*.product_images.*.max' => 'Each product image must not be larger than 2MB.',
        ];
    }
}