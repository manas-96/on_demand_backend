<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminPlaceController extends Controller
{
    /**
     * Fetch address suggestions using Google Places API (New) with Geocoding fallback.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('query', $request->query('input', '')));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $apiKey = config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4'));
        $suggestions = [];

        try {
            // 1. Try Google Places API (New) Autocomplete
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(5)->post('https://places.googleapis.com/v1/places:autocomplete', [
                'input' => $query,
            ]);

            if ($response->successful() && isset($response->json()['suggestions'])) {
                foreach ($response->json()['suggestions'] as $item) {
                    $pred = $item['placePrediction'] ?? null;
                    if ($pred && ! empty($pred['placeId'])) {
                        $suggestions[] = [
                            'place_id' => $pred['placeId'],
                            'description' => $pred['text']['text'] ?? '',
                            'main_text' => $pred['structuredFormat']['mainText']['text'] ?? ($pred['text']['text'] ?? ''),
                            'secondary_text' => $pred['structuredFormat']['secondaryText']['text'] ?? '',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Places autocomplete error: '.$e->getMessage());
        }

        // 2. Fallback to Geocoding API if no suggestions returned
        if (empty($suggestions)) {
            try {
                $geoRes = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'key' => $apiKey,
                ]);

                if ($geoRes->successful() && isset($geoRes->json()['results'])) {
                    foreach ($geoRes->json()['results'] as $item) {
                        $suggestions[] = [
                            'place_id' => $item['place_id'] ?? '',
                            'description' => $item['formatted_address'] ?? '',
                            'main_text' => $item['formatted_address'] ?? '',
                            'secondary_text' => '',
                            'lat' => $item['geometry']['location']['lat'] ?? null,
                            'lng' => $item['geometry']['location']['lng'] ?? null,
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Geocoding fallback error: '.$e->getMessage());
            }
        }

        return response()->json($suggestions);
    }

    /**
     * Fetch place details (formatted address, lat, lng) by place_id.
     */
    public function details(Request $request): JsonResponse
    {
        $placeId = trim((string) $request->query('place_id', ''));
        if (empty($placeId)) {
            return response()->json(['error' => 'place_id is required'], 422);
        }

        $apiKey = config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4'));

        try {
            // 1. Try Google Places API (New) Place Details
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
                'X-Goog-FieldMask' => 'id,formattedAddress,location',
            ])->timeout(5)->get("https://places.googleapis.com/v1/places/{$placeId}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['location'])) {
                    return response()->json([
                        'formatted_address' => $data['formattedAddress'] ?? '',
                        'lat' => $data['location']['latitude'] ?? null,
                        'lng' => $data['location']['longitude'] ?? null,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Place details error: '.$e->getMessage());
        }

        // 2. Fallback to Geocoding API by place_id
        try {
            $geoRes = Http::timeout(5)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'place_id' => $placeId,
                'key' => $apiKey,
            ]);

            if ($geoRes->successful() && isset($geoRes->json()['results'][0])) {
                $result = $geoRes->json()['results'][0];

                return response()->json([
                    'formatted_address' => $result['formatted_address'] ?? '',
                    'lat' => $result['geometry']['location']['lat'] ?? null,
                    'lng' => $result['geometry']['location']['lng'] ?? null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Geocoding place_id error: '.$e->getMessage());
        }

        return response()->json(['error' => 'Place details not found'], 404);
    }
}
