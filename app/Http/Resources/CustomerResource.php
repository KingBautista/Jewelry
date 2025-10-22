<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_login' => $this->user_login,
            'user_email' => $this->user_email,
            'user_status' => $this->user_status,
            'first_name' => $this->user_details['first_name'] ?? null,
            'last_name' => $this->user_details['last_name'] ?? null,
            'full_name' => $this->getFullNameAttribute(),
            'phone' => $this->user_details['phone'] ?? null,
            'address' => $this->user_details['address'] ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
