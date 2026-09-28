<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceCategoryResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image' => $this->image_url ?? $this->image,
            'minimum_booking_charge' => (float) ($this->effective_minimum_booking_charge ?? $this->minimum_booking_charge ?? 0.00),
            'is_active' => (bool) $this->is_active,
            'subcategories_count' => $this->whenCounted('subcategories'),
            'subcategories' => ServiceCategoryResource::collection($this->whenLoaded('subcategories')),
        ];
    }
}
