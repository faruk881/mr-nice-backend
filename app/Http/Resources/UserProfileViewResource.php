<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileViewResource extends JsonResource
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
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'stripe_customer_id' => $this->stripe_customer_id,
            'created_at' => $this->created_at,

            // Only include if loaded AND exists
            'courier_profile' => $this->when(
                $this->courierProfile !== null,
                $this->courierProfile
            ),
        ];
    }
}
