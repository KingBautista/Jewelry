<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTermScheduleResource extends JsonResource
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
            'payment_term_id' => $this->payment_term_id,
            'month_number' => $this->month_number,
            'percentage' => $this->percentage,
            'formatted_percentage' => $this->formatted_percentage,
            'description' => $this->description,
            'created_at' => $this->created_at->format('Y-m-d H:m:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
        ];
    }
}
