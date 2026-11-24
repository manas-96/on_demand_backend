<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PrivacyPolicyController extends Controller
{
    /**
     * Display public privacy policy web page.
     */
    public function show(): View
    {
        $settings = SystemSetting::getSettings();

        return view('privacy-policy', [
            'privacyPolicy' => $settings->privacy_policy,
            'updatedAt' => $settings->updated_at,
        ]);
    }

    /**
     * Return privacy policy data for API clients.
     */
    public function apiShow(): JsonResponse
    {
        $settings = SystemSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'privacy_policy' => $settings->privacy_policy,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
