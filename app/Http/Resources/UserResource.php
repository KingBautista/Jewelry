<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray(Request $request): array
  {
    $baseData = [
      'id' => $this->id,
      'user_login' => $this->user_login,
      'user_email' => $this->user_email,
      'first_name' => (isset($this->user_details['first_name'])) ? $this->user_details['first_name'] : '',
      'last_name' => (isset($this->user_details['last_name'])) ? $this->user_details['last_name'] : '',
      'nickname' => (isset($this->user_details['nickname'])) ? $this->user_details['nickname'] : '',
      'mobile_number' => (isset($this->user_details['mobile_number'])) ? $this->user_details['mobile_number'] : '',
      'contact_number' => (isset($this->user_details['contact_number'])) ? $this->user_details['contact_number'] : '',
      'biography' => (isset($this->user_details['biography'])) ? $this->user_details['biography'] : '',
      'attachment_file' => (isset($this->user_details['attachment_file'])) ? $this->user_details['attachment_file'] : '',
      'attachment_metadata' => (isset($this->user_details['attachment_metadata'])) ? $this->user_details['attachment_metadata'] : '',
      'user_role' => ($this->user_role) ? $this->user_role->name : 'Unassigned',
      'theme' => (isset($this->user_details['theme'])) ? $this->user_details['theme'] : '',
      'user_status' => ($this->user_status) ? 'Active' : 'Inactive',
      'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:m:s') : null
    ];

    // Add customer-specific fields if this is a customer
    if (isset($this->user_details['user_type']) && $this->user_details['user_type'] === 'customer') {
      $customerData = [
        'customer_code' => $this->customer_code,
        'full_name' => $this->full_name,
        'phone' => (isset($this->user_details['phone'])) ? $this->user_details['phone'] : '',
        'address' => (isset($this->user_details['address'])) ? $this->user_details['address'] : '',
        'city' => (isset($this->user_details['city'])) ? $this->user_details['city'] : '',
        'state' => (isset($this->user_details['state'])) ? $this->user_details['state'] : '',
        'postal_code' => (isset($this->user_details['postal_code'])) ? $this->user_details['postal_code'] : '',
        'country' => (isset($this->user_details['country'])) ? $this->user_details['country'] : '',
        'date_of_birth' => (isset($this->user_details['date_of_birth'])) ? $this->user_details['date_of_birth'] : '',
        'gender' => (isset($this->user_details['gender'])) ? $this->user_details['gender'] : '',
        'notes' => (isset($this->user_details['notes'])) ? $this->user_details['notes'] : '',
        'formatted_phone' => $this->formatted_phone,
        'formatted_address' => $this->formatted_address,
        'age' => $this->age,
        'customer_status_text' => $this->customer_status_text,
        'active' => $this->user_status == 1,
        'email' => $this->user_email, // For compatibility with frontend
        'created_at' => $this->created_at->format('Y-m-d H:m:s'),
      ];
      
      return array_merge($baseData, $customerData);
    }

    return $baseData;
  }
}
