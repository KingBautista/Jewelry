<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'payment_type' => $this->payment_type,
            'payment_method_id' => $this->payment_method_id,
            'payment_method_name' => $this->payment_method_name,
            'amount_paid' => $this->amount_paid,
            'formatted_amount_paid' => $this->formatted_amount_paid,
            'expected_amount' => $this->expected_amount,
            'formatted_expected_amount' => $this->formatted_expected_amount,
            'reference_number' => $this->reference_number,
            'receipt_image' => $this->receipt_image,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'rejection_reason' => $this->rejection_reason,
            'payment_date' => $this->payment_date?->format('Y-m-d'),
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'confirmed_by' => $this->confirmed_by,
            'confirmed_by_name' => $this->confirmed_by_name,
            'notes' => $this->notes,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            
            // Include related data when loaded
            'invoice' => $this->whenLoaded('invoice', function () {
                return [
                    'id' => $this->invoice->id,
                    'invoice_number' => $this->invoice->invoice_number,
                    'product_name' => $this->invoice->product_name,
                    'total_amount' => $this->invoice->total_amount,
                    'formatted_total_amount' => $this->invoice->formatted_total_amount,
                    'payment_schedules' => $this->invoice->paymentSchedules ?? [],
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->full_name,
                    'email' => $this->customer->user_email,
                    'phone' => $this->customer->phone,
                    'address' => $this->customer->formatted_address,
                ];
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'id' => $this->paymentMethod->id,
                    'bank_name' => $this->paymentMethod->bank_name,
                    'account_name' => $this->paymentMethod->account_name,
                    'account_number' => $this->paymentMethod->account_number,
                ];
            }),
            'confirmed_by_user' => $this->whenLoaded('confirmedBy', function () {
                return [
                    'id' => $this->confirmedBy->id,
                    'name' => $this->confirmedBy->full_name,
                    'email' => $this->confirmedBy->user_email,
                ];
            }),
            
            // Include payment schedules when available
            'payment_schedules' => $this->when(isset($this->payment_schedules), function () {
                return $this->payment_schedules;
            }),
            
            // Include paid schedules when available
            'paid_schedules' => $this->when(isset($this->paid_schedules), function () {
                return $this->paid_schedules;
            }),
        ];
    }
}