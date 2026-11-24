<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProviderAccountController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $providerProfile = ProviderProfile::where('user_id', $user->id)->first();
        $activeTab = $request->get('tab', 'profile');

        return view('provider.profile.index', compact('user', 'providerProfile', 'activeTab'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $providerProfile = ProviderProfile::where('user_id', $user->id)->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->where('role', $user->role->value)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->where('role', $user->role->value)->ignore($user->id)],
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
        ]);

        DB::transaction(function () use ($validated, $user, $providerProfile) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            if ($providerProfile) {
                $providerProfile->update([
                    'company_name' => $validated['company_name'],
                    'business_registration' => $validated['business_registration'] ?? null,
                    'address' => $validated['address'],
                    'service_radius_km' => $validated['service_radius_km'],
                ]);
            }
        });

        return back()->with('success', 'Company profile and account details updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('provider.profile.show', ['tab' => 'password'])
            ->with('success', 'Password updated successfully.');
    }
}
