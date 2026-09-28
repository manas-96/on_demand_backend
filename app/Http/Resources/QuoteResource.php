<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
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
            'service_request_id' => $this->service_request_id,
            'provider_id' => $this->provider_id,
            'technician_id' => $this->technician_id,
            'labor_subtotal' => (float) $this->labor_subtotal,
            'material_subtotal' => (float) $this->material_subtotal,
            'tax' => (float) $this->tax,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'is_approved_by_provider' => (bool) $this->is_approved_by_provider,
            'provider_approved_at' => $this->provider_approved_at?->toIso8601String(),
            'items' => $this->whenLoaded('items'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
