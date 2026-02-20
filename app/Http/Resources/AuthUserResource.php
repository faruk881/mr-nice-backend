<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'email'    => $this->email,
            'phone'    => $this->phone,
            'status'   => $this->status,
            'courier_profile' => $this->whenLoaded('courierProfile', function() {
                return [
                    'city' => $this->courierProfile->city,
                    'vehicle_type' => $this->courierProfile->vehicle_type,
                    'package_size' => $this->courierProfile->package_size,
                    'document_status' => $this->courierProfile->document_status,
                ];
            }),
            'roles'    => $this->roles->pluck('name'),
        ];
    }
}
