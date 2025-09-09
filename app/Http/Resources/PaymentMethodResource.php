<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'masked_account_number' => $this->masked_account_number,
            'description' => $this->description,
            'qr_code_image' => $this->qr_code_image,
            'qr_code_url' => $this->qr_code_url,
            'active' => $this->active,
            'status' => $this->active ? 'Active' : 'Inactive',
            'created_at' => $this->created_at->format('Y-m-d H:m:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
        ];
    }
}