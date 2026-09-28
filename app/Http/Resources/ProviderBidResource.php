<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderBidResource extends JsonResource
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
            'provider' => new UserResource($this->whenLoaded('provider')),
            'amount' => (float) $this->amount,
            'estimated_duration' => $this->estimated_duration,
            'availability_date' => $this->availability_date?->toDateString(),
            'message' => $this->message,
            'status' => $this->status?->value,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
