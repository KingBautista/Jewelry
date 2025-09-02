<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'discount_name' => $this->discount_name,
            'discount_value' => $this->discount_value,
            'discount_value_type' => $this->discount_value_type,
            'status' => $this->status ? 'Active' : 'Inactive',
            'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
            'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:m:s') : null,
        ];
    }
} 