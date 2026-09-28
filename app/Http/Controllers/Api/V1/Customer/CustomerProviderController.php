<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerProviderController extends Controller
{
    /**
     * Get active and approved providers with location data.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProviderProfile::with('user')
            ->where('is_approved', true)
            ->where('is_accepting_jobs', true);

        $providers = $query->get()->map(function ($provider) {
            return [
                'id' => 'PRV-'.$provider->id,
                'provider_id' => $provider->id,
                'name' => $provider->user?->name ?? 'Service Specialist',
                'company_name' => $provider->company_name,
                'address' => $provider->address ?? 'City Center',
                'rating' => '4.8',
                'jobs_count' => 120,
                'distance' => '1.5 km away',
                'eta' => '~15 mins arrival',
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
