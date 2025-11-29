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
    // Ensure user_details is loaded
    $userDetails = $this->user_details ?? [];
    
    $baseData = [
      'id' => $this->id,
      'user_login' => $this->user_login,
      'user_email' => $this->user_email,
      'first_name' => $userDetails['first_name'] ?? '',
      'last_name' => $userDetails['last_name'] ?? '',
      'nickname' => $userDetails['nickname'] ?? '',
      'mobile_number' => $userDetails['mobile_number'] ?? '',
      'contact_number' => $userDetails['contact_number'] ?? '',
      'biography' => $userDetails['biography'] ?? '',
      'attachment_file' => $userDetails['attachment_file'] ?? '',
      'attachment_metadata' => $userDetails['attachment_metadata'] ?? '',
      'user_role' => $this->userRole ? [
        'id' => $this->userRole->id,
        'name' => $this->userRole->name,
        'active' => $this->userRole->active,
        'is_super_admin' => $this->userRole->is_super_admin,
      ] : null,
      'user_role_id' => $this->user_role_id,
      'user_details' => $userDetails, // Include full user_details for frontend
      'theme' => $userDetails['theme'] ?? '',
      'user_status' => ($this->user_status) ? 'Active' : 'Inactive',
      'updated_at' => $this->updated_at->format('Y-m-d H:m:s'),
      'deleted_at' => ($this->deleted_at) ? $this->deleted_at->format('Y-m-d H:m:s') : null
    ];

    // Add customer-specific fields if this is a customer
    if (isset($userDetails['user_type']) && $userDetails['user_type'] === 'customer') {
      $customerData = [
        'customer_code' => $this->customer_code,
        'full_name' => $this->full_name,
        'phone' => $userDetails['phone'] ?? '',
        'address' => $userDetails['address'] ?? '',
        'city' => $userDetails['city'] ?? '',
        'state' => $userDetails['state'] ?? '',
        'postal_code' => $userDetails['postal_code'] ?? '',
        'country' => $userDetails['country'] ?? '',
        'date_of_birth' => $userDetails['date_of_birth'] ?? '',
        'gender' => $userDetails['gender'] ?? '',
        'notes' => $userDetails['notes'] ?? '',
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
