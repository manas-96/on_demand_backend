<?php

namespace App\Services\Matching;

use App\Enums\NotificationType;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Services\EmailNotificationService;
use App\Services\PushNotificationService;
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
        // No self-dealing: never offer a customer's job to a provider company owned by the same person (same email).
        $customerEmail = $serviceRequest->customer()->value('email');
        $excludeOwnCompany = function ($query) use ($customerEmail): void {
            if ($customerEmail) {
                $query->whereDoesntHave('user', fn ($user) => $user->where('email', $customerEmail));
            }
        };

        // 1. Direct Request: Match targeted provider directly if specified
        if ($serviceRequest->target_provider_id) {
            $directProfiles = ProviderProfile::query()
                ->with(['user', 'technicians'])
                ->where('user_id', $serviceRequest->target_provider_id)
                ->where('is_approved', true)
                ->tap($excludeOwnCompany)
                ->get();

            if ($directProfiles->isNotEmpty()) {
                return $directProfiles;
            }
        }

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

        $hasCoordinates = $lat != 0.0 && $lng != 0.0;
        $minFunc = DB::connection()->getDriverName() === 'sqlite' ? 'min' : 'least';
        $haversine = "(6371 * acos({$minFunc}(1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

        // Query eligible provider profiles using Haversine formula when valid coordinates exist
        $query = ProviderProfile::query()
            ->with(['user', 'technicians'])
            ->where('is_approved', true)
            ->tap($excludeOwnCompany)
            ->where('is_accepting_jobs', true);

        if ($hasCoordinates) {
            $query->selectRaw("*, {$haversine} AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereRaw("{$haversine} <= service_radius_km", [$lat, $lng, $lat]);
        }

        // Filter by offered category hierarchy or service
        $query->where(function ($q) use ($allCategoryIds, $serviceId) {
            if (! empty($allCategoryIds)) {
                $q->whereHas('services', function ($subQ) use ($allCategoryIds) {
                    $subQ->whereIn('service_category_id', $allCategoryIds);
                });
            } elseif ($serviceId) {
                $q->whereHas('providerServices', function ($subQ) use ($serviceId) {
                    $subQ->where('service_id', $serviceId)
                        ->where('is_offered', true);
                });
            }
        });

        if ($hasCoordinates) {
            $query->orderByRaw("{$haversine} ASC", [$lat, $lng, $lat]);
        }

        $results = $query->get();

        // Fallback 1: If strict radius matching yields no providers, match active providers by category hierarchy regardless of radius
        if ($results->isEmpty() && $hasCoordinates) {
            $results = ProviderProfile::query()
                ->with(['user', 'technicians'])
                ->where('is_approved', true)
                ->tap($excludeOwnCompany)
                ->where('is_accepting_jobs', true)
                ->where(function ($q) use ($allCategoryIds, $serviceId) {
                    if (! empty($allCategoryIds)) {
                        $q->whereHas('services', function ($subQ) use ($allCategoryIds) {
                            $subQ->whereIn('service_category_id', $allCategoryIds);
                        });
                    } elseif ($serviceId) {
                        $q->whereHas('providerServices', function ($subQ) use ($serviceId) {
                            $subQ->where('service_id', $serviceId)
                                ->where('is_offered', true);
                        });
                    }
                })
                ->get();
        }

        // Fallback 2: If category matching yields no results, return all active & approved provider profiles
        if ($results->isEmpty()) {
            $results = ProviderProfile::query()
                ->with(['user', 'technicians'])
                ->where('is_approved', true)
                ->tap($excludeOwnCompany)
                ->where('is_accepting_jobs', true)
                ->get();
        }

        return $results;
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
        $providers = collect();

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
            if ($profile->user) {
                $providers->push($profile->user);
            }
        }

        if ($providers->isNotEmpty()) {
            app(EmailNotificationService::class)->sendRequestCreatedToProviders($serviceRequest, $providers);

            app(PushNotificationService::class)->sendToUsers(
                userIds: $providers->pluck('id')->toArray(),
                title: 'New Service Request Available',
                body: "A new service request (#{$serviceRequest->request_number}) matching your location and services is available.",
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => (string) $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_new_request_'.now()->timestamp
            );
        }

        return $createdInvitations;
    }
}
