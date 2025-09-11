<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'product_name' => $this->product_name,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'product_image' => $this->product_image,
            'payment_term_id' => $this->payment_term_id,
            'payment_term_name' => $this->payment_term_name,
            'tax_id' => $this->tax_id,
            'tax_name' => $this->tax_name,
            'fee_id' => $this->fee_id,
            'fee_name' => $this->fee_name,
            'discount_id' => $this->discount_id,
            'discount_name' => $this->discount_name,
            'shipping_address' => $this->shipping_address,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'status' => $this->status,
            'status_text' => $this->status_text,
            'subtotal' => $this->subtotal,
            'formatted_subtotal' => $this->formatted_subtotal,
            'tax_amount' => $this->tax_amount,
            'formatted_tax_amount' => $this->formatted_tax_amount,
            'fee_amount' => $this->fee_amount,
            'formatted_fee_amount' => $this->formatted_fee_amount,
            'discount_amount' => $this->discount_amount,
            'formatted_discount_amount' => $this->formatted_discount_amount,
            'total_amount' => $this->total_amount,
            'formatted_total_amount' => $this->formatted_total_amount,
            'notes' => $this->notes,
            'active' => $this->active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            
            // Include related data when loaded
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->full_name,
                    'email' => $this->customer->user_email,
                    'phone' => $this->customer->phone,
                    'address' => $this->customer->formatted_address,
                ];
            }),
            'payment_term' => $this->whenLoaded('paymentTerm', function () {
                return [
                    'id' => $this->paymentTerm->id,
                    'name' => $this->paymentTerm->name,
                    'code' => $this->paymentTerm->code,
                    'term_months' => $this->paymentTerm->term_months,
                ];
            }),
            'tax' => $this->whenLoaded('tax', function () {
                return [
                    'id' => $this->tax->id,
                    'name' => $this->tax->name,
                    'code' => $this->tax->code,
                    'rate' => $this->tax->rate,
                    'type' => $this->tax->type ?? 'percentage',
                ];
            }),
            'fee' => $this->whenLoaded('fee', function () {
                return [
                    'id' => $this->fee->id,
                    'name' => $this->fee->name,
                    'code' => $this->fee->code,
                    'amount' => $this->fee->amount,
                    'type' => $this->fee->type,
                ];
            }),
            'discount' => $this->whenLoaded('discount', function () {
                return [
                    'id' => $this->discount->id,
                    'name' => $this->discount->name,
                    'code' => $this->discount->code,
                    'amount' => $this->discount->amount,
                    'type' => $this->discount->type,
                ];
            }),
        ];
    }
}