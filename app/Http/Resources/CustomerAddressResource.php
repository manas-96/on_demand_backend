<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
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
            'user_id' => $this->user_id,
            'label' => $this->label,
            'address_line' => $this->address_line,
            'house_no' => $this->house_no,
            'street_address' => $this->street_address,
            'city' => $this->city,
            'landmark' => $this->landmark,
            'pincode' => $this->pincode,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
