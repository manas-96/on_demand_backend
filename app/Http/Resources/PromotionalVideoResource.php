<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionalVideoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'youtube_video_id' => $this->youtube_id,
            'video_url' => $this->video,
            'thumbnail_url' => $this->thumbnail_url,
            'category_name' => $this->category?->name ?? 'General Service',
            'service_category_id' => $this->service_category_id,
            'duration' => '2:30',
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
