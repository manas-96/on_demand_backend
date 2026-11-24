<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Auth\EmailOtpService;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Provider self-onboarding: register → verify email (OTP) → wait for admin approval → panel.
 */
class ProviderRegistrationController extends Controller
{
    public function __construct(private EmailOtpService $emailOtp) {}

    /**
     * Show public provider registration form.
     */
    public function showRegistrationForm(): View
    {
        $categories = ServiceCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['subcategories' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('auth.register-provider', compact('categories'));
    }

    /**
     * Create the provider account as unverified and unapproved, then send the email code.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Provider->value)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->where('role', UserRole::Provider->value)],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', 'exists:service_categories,id'],
        ], [
            'latitude.required' => 'Please pin your business location on the map.',
            'longitude.required' => 'Please pin your business location on the map.',
            'categories.required' => 'Select at least one service category you offer.',
        ]);

        /** @var User $user */
        $user = DB::transaction(function () use ($validated) {
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
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'service_radius_km' => $validated['service_radius_km'],
                'commission' => SystemSetting::getSettings()->platform_fee,
                // Admin must review a self-registered provider before it can receive jobs.
                'is_approved' => false,
                'is_accepting_jobs' => true,
            ]);

            $this->attachServicesForCategories($providerProfile, $validated['categories']);

            return $user;
        });

        $this->emailOtp->issue($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('provider.onboarding.verify-email')
            ->with('success', "We sent a 6-digit code to {$user->email}.");
    }

    public function showVerifyEmail(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('provider.onboarding.pending');
        }

        return view('auth.provider-verify-email', ['email' => $request->user()->email]);
    }

    public function verifyEmail(Request $request, EmailNotificationService $emails): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('provider.onboarding.pending');
        }

        $result = $this->emailOtp->verify($user, $validated['otp']);

        if ($result !== EmailOtpService::VERIFIED) {
            $message = match ($result) {
                EmailOtpService::EXPIRED => 'This code has expired. Request a new one below.',
                EmailOtpService::TOO_MANY_ATTEMPTS => 'Too many wrong attempts. Request a new code below.',
                default => 'That code is not correct. Please check and try again.',
            };

            return back()->withErrors(['otp' => $message]);
        }

        if ($user->providerProfile) {
            $emails->sendProviderRegisteredToAdmins($user->providerProfile);
        }

        return redirect()
            ->route('provider.onboarding.pending')
            ->with('success', 'Email verified. Your profile is now with our team for review.');
    }

    public function resendVerificationCode(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('provider.onboarding.pending');
        }

        $wait = $this->emailOtp->secondsUntilResend($user);

        if ($wait > 0) {
            return back()->withErrors(['otp' => "Please wait {$wait} seconds before requesting another code."]);
        }

        $this->emailOtp->issue($user);

        return back()->with('success', "A new code was sent to {$user->email}.");
    }

    public function pending(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('provider.onboarding.verify-email');
        }

        if ($user->providerProfile?->is_approved) {
            return redirect()->route('provider.dashboard');
        }

        return view('auth.provider-pending-approval', ['providerProfile' => $user->providerProfile]);
    }

    /**
     * Selecting a main category also offers every service in its sub-categories.
     *
     * @param  array<int, int|string>  $categoryIds
     */
    private function attachServicesForCategories(ProviderProfile $providerProfile, array $categoryIds): void
    {
        $categoryIds = array_map('intval', $categoryIds);
        $withChildren = ServiceCategory::whereIn('parent_id', $categoryIds)->pluck('id')->merge($categoryIds)->unique();

        Service::whereIn('service_category_id', $withChildren)->pluck('id')->each(
            fn (int $serviceId) => ProviderService::create([
                'provider_profile_id' => $providerProfile->id,
                'service_id' => $serviceId,
                'is_offered' => true,
            ])
        );
    }
}
