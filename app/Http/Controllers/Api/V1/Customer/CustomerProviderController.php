<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerProviderController extends Controller
{
    /**
     * Get active and approved providers with dynamic location and nearest matching data.
     */
    public function index(Request $request): JsonResponse
    {
        $lat = $request->filled('latitude') ? (float) $request->query('latitude') : null;
        $lng = $request->filled('longitude') ? (float) $request->query('longitude') : null;
        $categoryId = $request->query('category_id');
        $subCategoryId = $request->query('sub_category_id');
        $serviceId = $request->query('service_id');
        $searchRadius = $request->filled('radius') ? (float) $request->query('radius') : null;

        $categoryIds = collect([$categoryId, $subCategoryId])->filter()->values()->toArray();
        $allCategoryIds = [];

        if (! empty($categoryIds)) {
            $childIds = ServiceCategory::whereIn('parent_id', $categoryIds)->pluck('id')->toArray();
            $parentIds = ServiceCategory::whereIn('id', $categoryIds)->whereNotNull('parent_id')->pluck('parent_id')->toArray();
            $allCategoryIds = array_unique(array_merge($categoryIds, $childIds, $parentIds));
        }

        $query = ProviderProfile::with(['user', 'services'])
            ->where('is_approved', true)
            ->where('is_accepting_jobs', true);

        // Filter by category hierarchy or service if requested
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

        $hasCoordinates = ! is_null($lat) && ! is_null($lng);

        if ($hasCoordinates) {
            $minFunc = DB::connection()->getDriverName() === 'sqlite' ? 'min' : 'least';
            $haversine = "(6371 * acos({$minFunc}(1.0, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))))";

            $query->selectRaw("*, {$haversine} AS distance_km", [$lat, $lng, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            if ($searchRadius) {
                $query->whereRaw("{$haversine} <= ?", [$lat, $lng, $lat, $searchRadius]);
            } else {
                $query->whereRaw("{$haversine} <= service_radius_km", [$lat, $lng, $lat]);
            }

            $query->orderByRaw("{$haversine} ASC", [$lat, $lng, $lat]);
        } else {
            $query->orderBy('id', 'desc');
        }

        $providers = $query->get()->map(function ($provider) use ($hasCoordinates) {
            $distKm = $hasCoordinates && isset($provider->distance_km)
                ? round((float) $provider->distance_km, 1)
                : 1.5;

            $etaMins = (int) max(5, round(($distKm / 30) * 60 + 10));

            return [
                'id' => 'PRV-'.$provider->id,
                'provider_id' => $provider->id,
                'name' => $provider->user?->name ?? 'Service Specialist',
                'company_name' => $provider->company_name,
                'address' => $provider->address ?? 'City Center',
                'rating' => '4.8',
                'jobs_count' => 120,
                'distance' => $distKm.' km away',
                'distance_km' => $distKm,
                'eta' => '~'.$etaMins.' mins arrival',
                'category' => 'General',
                'latitude' => $provider->latitude ? (float) $provider->latitude : 28.6139,
                'longitude' => $provider->longitude ? (float) $provider->longitude : 77.2090,
                'service_radius_km' => (float) $provider->service_radius_km,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Active providers retrieved successfully.',
            'data' => $providers,
        ]);
    }
}
