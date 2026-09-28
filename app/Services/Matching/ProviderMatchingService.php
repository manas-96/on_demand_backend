<?php

namespace App\Services\Matching;

use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProviderMatchingService
{
    /**
     * Match eligible nearby providers for a broadcast service request.
     *
     * @return Collection<int, ProviderProfile>
     */
    public function matchEligibleProviders(ServiceRequest $serviceRequest): Collection
    {
        $lat = (float) $serviceRequest->latitude;
        $lng = (float) $serviceRequest->longitude;
        $categoryId = $serviceRequest->service_category_id;
        $subCategoryId = $serviceRequest->sub_category_id;
        $serviceId = $serviceRequest->service_id;

        $categoryIds = collect([$categoryId, $subCategoryId])->filter()->values()->toArray();
        $allCategoryIds = [];

        if (! empty($categoryIds)) {
            $childIds = ServiceCategory::whereIn('parent_id', $categoryIds)->pluck('id')->toArray();
            $parentIds = ServiceCategory::whereIn('id', $categoryIds)->whereNotNull('parent_id')->pluck('parent_id')->toArray();
            $allCategoryIds = array_unique(array_merge($categoryIds, $childIds, $parentIds));
        }

        $minFunc = DB::connection()->getDriverName() === 'sqlite' ? 'min' : 'least';
        $haversine = "(6371 * acos({$minFunc}(1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

        // Query eligible provider profiles using Haversine formula
        return ProviderProfile::query()
            ->with(['user', 'technicians'])
            ->selectRaw("*, {$haversine} AS distance_km", [$lat, $lng, $lat])
            ->where('is_approved', true)
            ->where('is_accepting_jobs', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= service_radius_km", [$lat, $lng, $lat])
            // Filter by offered category hierarchy or service
            ->where(function ($query) use ($allCategoryIds, $serviceId) {
                if (! empty($allCategoryIds)) {
                    $query->whereHas('services', function ($q) use ($allCategoryIds) {
                        $q->whereIn('service_category_id', $allCategoryIds);
                    });
                } elseif ($serviceId) {
                    $query->whereHas('providerServices', function ($q) use ($serviceId) {
                        $q->where('service_id', $serviceId)
                            ->where('is_offered', true);
                    });
                }
            })
            // Filter by having active/approved technicians
            ->whereHas('technicians', function ($query) {
                $query->where('is_active', true)
                    ->where('is_approved', true);
            })
            ->orderByRaw("{$haversine} ASC", [$lat, $lng, $lat])
            ->get();
    }

    /**
     * Dispatch job request invitations to matched providers.
     *
     * @return Collection<int, ProviderJobRequest>
     */
    public function dispatchInvitations(ServiceRequest $serviceRequest): Collection
    {
        $matchedProfiles = $this->matchEligibleProviders($serviceRequest);
        $createdInvitations = collect();

        foreach ($matchedProfiles as $profile) {
            $invitation = ProviderJobRequest::create([
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $profile->user_id,
                'provider_profile_id' => $profile->id,
                'status' => 'sent',
                'sent_at' => now(),
                'expires_at' => now()->addHours(24),
            ]);

            $createdInvitations->push($invitation);
        }

        return $createdInvitations;
    }
}
