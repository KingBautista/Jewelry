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
            'product_images' => $this->product_images,
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
            'status' => $this->status ?: 'draft',
            'status_text' => $this->status_text ?: 'Draft',
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
            'payment_status' => $this->payment_status,
            'payment_status_text' => $this->payment_status_text,
            'total_paid_amount' => $this->total_paid_amount,
            'formatted_total_paid_amount' => $this->formatted_total_paid_amount,
            'remaining_balance' => $this->remaining_balance,
            'formatted_remaining_balance' => $this->formatted_remaining_balance,
            'next_payment_due_date' => $this->next_payment_due_date?->format('Y-m-d'),
            'payment_plan_created' => $this->payment_plan_created,
            'item_status' => $this->item_status,
            'item_status_text' => $this->item_status_text,
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
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'description' => $item->description,
                        'price' => $item->price,
                        'formatted_price' => $item->formatted_price,
                        'product_images' => $item->product_images,
                    ];
                });
            }),
        ];
    }
}