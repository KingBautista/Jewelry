<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'type' => $this->type,
            'description' => $this->description,
            'active' => $this->active,
            'status' => $this->active ? 'Active' : 'Inactive',
            'created_at' => $this->created_at->format('Y-m-d H:m:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
        ];
    }
}