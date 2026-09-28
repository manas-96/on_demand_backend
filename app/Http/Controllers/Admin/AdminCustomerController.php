<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminCustomerController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->where('role', 'customer')->withCount(['customerAddresses', 'customerAddresses as request_count']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address_line' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $customer */
        $customer = null;

        DB::transaction(function () use ($validated, &$customer) {
            $customer = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => UserRole::Customer,
                'is_active' => true,
            ]);

            if (! empty($validated['address_line'])) {
                CustomerAddress::create([
                    'user_id' => $customer->id,
                    'label' => 'Primary Address',
                    'address_line' => $validated['address_line'],
                    'latitude' => 28.6139,
                    'longitude' => 77.2090,
                    'is_default' => true,
                ]);
            }
        });

        return redirect()
            ->route('admin.customers.index')
            ->with('success', "Customer account '{$customer->name}' created successfully.");
    }

    public function show(int $id): View
    {
        $customer = User::with(['customerAddresses'])->where('role', 'customer')->findOrFail($id);

        return view('admin.customers.show', compact('customer'));
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        $customer->update(['is_active' => ! $customer->is_active]);

        $status = $customer->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Customer account {$customer->email} has been {$status}.");
    }
}
