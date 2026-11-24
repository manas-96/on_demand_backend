<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProviderController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProviderProfile::with(['user', 'providerServices.service']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('business_registration', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'unapproved') {
                $query->where('is_approved', false);
            }
        }

        $providers = $query->latest()->paginate(15);

        $mapProviders = ProviderProfile::select(['id', 'company_name', 'address', 'latitude', 'longitude', 'service_radius_km', 'is_approved'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->company_name,
                    'address' => $p->address ?? 'Address not specified',
                    'lat' => (float) ($p->latitude ?? 40.7128),
                    'lng' => (float) ($p->longitude ?? -74.0060),
                    'radius' => (float) ($p->service_radius_km ?? 25.0),
                    'is_approved' => $p->is_approved,
                    'url' => route('admin.providers.show', $p->id),
                ];
            });

        return view('admin.providers.index', compact('providers', 'mapProviders'));
    }

    public function create(): View
    {
        $categories = ServiceCategory::whereNull('parent_id')
            ->with(['subcategories' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $defaultCommission = SystemSetting::getSettings()->platform_fee;

        return view('admin.providers.create', compact('categories', 'defaultCommission'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Provider->value)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->where('role', UserRole::Provider->value)],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:service_categories,id'],
        ]);

        /** @var ProviderProfile $providerProfile */
        $providerProfile = null;

        DB::transaction(function () use ($validated, &$providerProfile) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Provider,
                'is_active' => true,
            ]);

            // Created by an admin, so the address is trusted and no OTP step is needed.
            $user->markEmailAsVerified();

            $providerProfile = ProviderProfile::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'business_registration' => $validated['business_registration'] ?? null,
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'service_radius_km' => $validated['service_radius_km'],
                'commission' => $validated['commission'] ?? SystemSetting::getSettings()->platform_fee,
                'is_approved' => true,
                'is_accepting_jobs' => true,
            ]);

            if (! empty($validated['categories'])) {
                $categoryIds = ServiceCategory::whereIn('parent_id', $validated['categories'])->pluck('id')->merge($validated['categories'])->unique();
                $serviceIds = Service::whereIn('service_category_id', $categoryIds)->pluck('id');
                foreach ($serviceIds as $serviceId) {
                    ProviderService::create([
                        'provider_profile_id' => $providerProfile->id,
                        'service_id' => $serviceId,
                        'is_offered' => true,
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.providers.index')
            ->with('success', "Service Provider company '{$providerProfile->company_name}' created successfully.");
    }

    public function show(int $id): View
    {
        $providerProfile = ProviderProfile::with(['user', 'technicians.user', 'providerServices.service.category.parent'])->findOrFail($id);

        return view('admin.providers.show', compact('providerProfile'));
    }

    public function edit(int $id): View
    {
        $providerProfile = ProviderProfile::with(['user', 'providerServices.service'])->findOrFail($id);
        $categories = ServiceCategory::whereNull('parent_id')
            ->with(['subcategories' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $offeredCategoryIds = Service::whereIn('id', $providerProfile->providerServices->pluck('service_id'))
            ->pluck('service_category_id')
            ->unique()
            ->toArray();

        return view('admin.providers.edit', compact('providerProfile', 'categories', 'offeredCategoryIds'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $providerProfile = ProviderProfile::with('user')->findOrFail($id);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'business_registration' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'service_radius_km' => ['required', 'numeric', 'min:1', 'max:500'],
            'commission' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Provider->value)->ignore($providerProfile->user_id)],
            'phone' => ['nullable', 'string', 'max:50', Rule::unique('users', 'phone')->where('role', UserRole::Provider->value)->ignore($providerProfile->user_id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:service_categories,id'],
        ]);

        DB::transaction(function () use ($validated, $providerProfile) {
            $userPayload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ];

            if (! empty($validated['password'])) {
                $userPayload['password'] = Hash::make($validated['password']);
            }

            $providerProfile->user->update($userPayload);

            $providerProfile->update([
                'company_name' => $validated['company_name'],
                'business_registration' => $validated['business_registration'] ?? null,
                'address' => $validated['address'],
                'latitude' => $validated['latitude'] ?? $providerProfile->latitude,
                'longitude' => $validated['longitude'] ?? $providerProfile->longitude,
                'service_radius_km' => $validated['service_radius_km'],
                'commission' => $validated['commission'] ?? $providerProfile->commission,
            ]);

            // Sync services linked to selected service categories
            ProviderService::where('provider_profile_id', $providerProfile->id)->delete();
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

        return redirect()
            ->route('admin.providers.index')
            ->with('success', "Service Provider company '{$providerProfile->company_name}' updated successfully.");
    }

    public function toggleApproval(int $id): RedirectResponse
    {
        $profile = ProviderProfile::with('user')->findOrFail($id);
        $profile->update(['is_approved' => ! $profile->is_approved]);

        $status = $profile->is_approved ? 'approved' : 'unapproved';

        if ($profile->is_approved) {
            app(EmailNotificationService::class)->sendProviderApproved($profile);
        }

        return back()->with('success', "Provider {$profile->company_name} is now {$status}.");
    }

    public function toggleJobAcceptance(int $id): RedirectResponse
    {
        $profile = ProviderProfile::findOrFail($id);
        $profile->update(['is_accepting_jobs' => ! $profile->is_accepting_jobs]);

        $status = $profile->is_accepting_jobs ? 'accepting jobs' : 'paused from accepting jobs';

        return back()->with('success', "Provider {$profile->company_name} is now {$status}.");
    }
}
