<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Register or update FCM device token for authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_token' => ['required', 'string'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
            'device_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $tokenString = trim($validated['device_token']);
        $platform = strtolower($validated['platform'] ?? 'android');
        $deviceId = $validated['device_id'] ?? null;

        // Upsert device token for user
        $deviceToken = DeviceToken::where('user_id', $user->id)
            ->where(function ($query) use ($tokenString, $deviceId) {
                $query->where('device_token', $tokenString);
                if (! empty($deviceId)) {
                    $query->orWhere('device_id', $deviceId);
                }
            })
            ->first();

        if ($deviceToken) {
            $deviceToken->update([
                'device_token' => $tokenString,
                'platform' => $platform,
                'device_id' => $deviceId ?? $deviceToken->device_id,
                'is_active' => true,
                'last_used_at' => now(),
            ]);
        } else {
            $deviceToken = DeviceToken::create([
                'user_id' => $user->id,
                'device_token' => $tokenString,
                'platform' => $platform,
                'device_id' => $deviceId,
                'is_active' => true,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Device token registered successfully.',
            'data' => $deviceToken,
        ]);
    }

    /**
     * Deactivate or remove FCM device token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokenString = $request->input('device_token');

        $query = DeviceToken::where('user_id', $user->id);

        if (! empty($tokenString)) {
            $query->where('device_token', trim($tokenString));
        }

        $query->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device token removed successfully.',
        ]);
    }
}
