<?php

namespace App\Http\Controllers\Provider;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProviderTechnicianController extends Controller
{
    /**
     * Display technicians belonging exclusively to the authenticated provider.
     */
    public function index(Request $request): View
    {
        $providerId = $request->user()->id;

        $query = TechnicianProfile::with(['user'])
            ->where('provider_id', $providerId);

        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $technicians = $query->latest()->paginate(15);

        return view('provider.technicians.index', compact('technicians'));
    }

    /**
     * Show form to add a new technician for this provider.
     */
    public function create(): View
    {
        return view('provider.technicians.create');
    }

    /**
     * Store a new technician under the authenticated provider.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->where('role', UserRole::Technician->value)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->where('role', UserRole::Technician->value)],
            'password' => ['required', 'string', 'min:8'],
            'qualification' => ['nullable', 'string', 'max:255'],
        ]);

        $provider = $request->user();
        $providerProfile = $provider->providerProfile;

        // 1. Create User account with Technician role
        $technicianUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Technician,
            'is_active' => true,
        ]);

        // 2. Create TechnicianProfile attached strictly to this provider
        TechnicianProfile::create([
            'user_id' => $technicianUser->id,
            'provider_id' => $provider->id,
            'provider_profile_id' => $providerProfile?->id,
            'qualification' => $validated['qualification'] ?? null,
            'is_active' => true,
            'is_approved' => true,
            'duty_status' => 'off_duty',
        ]);

        return redirect()
            ->route('provider.technicians.index')
            ->with('success', "Technician {$technicianUser->name} added successfully to your fleet.");
    }

    /**
     * Toggle active status of a technician belonging to this provider.
     */
    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        $providerId = $request->user()->id;

        // Scoped strictly to authenticated provider
        $technician = TechnicianProfile::where('provider_id', $providerId)->findOrFail($id);

        $technician->update(['is_active' => ! $technician->is_active]);

        $statusStr = $technician->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Technician {$technician->user->name} has been {$statusStr}.");
    }
}
