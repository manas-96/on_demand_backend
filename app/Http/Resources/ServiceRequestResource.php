<?php

namespace App\Http\Resources;

use App\Enums\InvoiceStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isPaid = $this->invoices()->where('status', InvoiceStatus::Paid)->exists();

        return [
            'id' => $this->id,
            'request_number' => $this->request_number,
            'customer_id' => $this->customer_id,
            'is_paid' => $isPaid,
            'payment_status' => $isPaid ? 'paid' : 'pending',
            'customer' => new UserResource($this->whenLoaded('customer')),
            'upfront_deposit_paid' => (float) ($this->subCategory?->effective_minimum_booking_charge ?? $this->category?->effective_minimum_booking_charge ?? 250.00),
            'minimum_booking_charge' => (float) ($this->subCategory?->effective_minimum_booking_charge ?? $this->category?->effective_minimum_booking_charge ?? 250.00),
            'service_category_id' => $this->service_category_id,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'icon' => $this->category->icon,
                    'image' => $this->category->image_url ?? $this->category->image,
                    'minimum_booking_charge' => (float) $this->category->effective_minimum_booking_charge,
                ];
            }),
            'sub_category_id' => $this->sub_category_id,
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                    'slug' => $this->subCategory->slug,
                    'minimum_booking_charge' => (float) $this->subCategory->effective_minimum_booking_charge,
                ];
            }),
            'service_id' => $this->service_id,
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'base_price' => $this->service->base_price,
                ];
            }),
            'request_type' => $this->request_type?->value,
            'description' => $this->description,
            'address_line' => $this->address_line,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'preferred_date' => $this->preferred_date?->toDateString(),
            'priority' => $this->priority,
            'status' => $this->status?->value,
            'provider_name' => $this->whenLoaded('targetProvider', function () {
                return $this->targetProvider?->name;
            }),
            'technician_name' => $this->whenLoaded('jobAssignments', function () {
                return $this->jobAssignments->first()?->technician?->name;
            }),
            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->map(function ($photo) {
                    $path = $photo->file_path;
                    if (Str::startsWith($path, ['http://', 'https://'])) {
                        return $path;
                    }

                    $cleanPath = ltrim($path, '/');
                    if (Str::startsWith($cleanPath, 'storage/')) {
                        $cleanPath = Str::after($cleanPath, 'storage/');
                    } elseif (Str::startsWith($cleanPath, 'public/')) {
                        $cleanPath = Str::after($cleanPath, 'public/');
                    }

                    return asset('storage/'.$cleanPath);
                });
            }),
            'review' => $this->whenLoaded('review', function () {
                return [
                    'id' => $this->review->id,
                    'rating' => (int) $this->review->rating,
                    'comment' => $this->review->comment,
                    'created_at' => $this->review->created_at?->toIso8601String(),
                    'updated_at' => $this->review->updated_at?->toIso8601String(),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
