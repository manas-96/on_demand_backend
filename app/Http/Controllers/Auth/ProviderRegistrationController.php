<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProviderRegistrationController extends Controller
{
    /**
     * Show public provider registration form.
     */
    public function showRegistrationForm(): View
    {
        $categories = ServiceCategory::where('is_active', true)->get();

        return view('auth.register-provider', compact('categories'));
    }

    /**
     * Handle provider registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:service_categories,id'],
        ]);

        /** @var User $user */
        $user = null;

        DB::transaction(function () use ($validated, &$user) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Provider,
                'is_active' => true,
            ]);

            $providerProfile = ProviderProfile::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'business_registration' => $validated['business_registration'] ?? null,
                'address' => $validated['address'],
                'service_radius_km' => $validated['service_radius_km'],
                'commission' => 10.00,
                'is_approved' => true,
                'is_accepting_jobs' => true,
            ]);

            if (! empty($validated['categories'])) {
                $serviceIds = Service::whereIn('service_category_id', $validated['categories'])->pluck('id');
                foreach ($serviceIds as $serviceId) {
                    ProviderService::create([
                        'provider_profile_id' => $providerProfile->id,
                        'service_id' => $serviceId,
                        'is_offered' => true,
                    ]);
                }
            }
        });

        Auth::login($user);

        return redirect()
            ->route('provider.dashboard')
            ->with('success', "Welcome to ServicePlatform! Provider account '{$validated['company_name']}' registered successfully.");
    }
}
