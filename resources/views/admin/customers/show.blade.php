@extends('layouts.admin')

@section('title', 'Customer Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.customers.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">← Back to Customers</a>
            <h1 class="text-2xl font-bold text-white">{{ $customer->name }}</h1>
            <p class="text-xs text-slate-400">Customer ID #{{ $customer->id }} • Member since {{ $customer->created_at->inDisplayTimezone()->format('M d, Y') }}</p>
        </div>
        <form method="POST" action="{{ route('admin.customers.toggle-status', $customer->id) }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-xl {{ $customer->is_active ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }} text-xs font-semibold">
                {{ $customer->is_active ? 'Suspend Account' : 'Activate Account' }}
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Information -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Contact Details</h2>
            <div class="space-y-2 text-sm">
                <div><span class="text-slate-500">Email:</span> <span class="text-white font-medium">{{ $customer->email }}</span></div>
                <div><span class="text-slate-500">Phone:</span> <span class="text-white font-medium">{{ $customer->phone ?? 'Not provided' }}</span></div>
                <div><span class="text-slate-500">Status:</span> 
                    <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $customer->is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                        {{ $customer->is_active ? 'Active' : 'Suspended' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Saved Addresses -->
        <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Saved Addresses</h2>
            <div class="space-y-3">
                @forelse($customer->customerAddresses as $addr)
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-500/20 text-indigo-400 mr-2">{{ $addr->label }}</span>
                            <span class="text-sm text-slate-200">{{ $addr->address_line }}</span>
                            <div class="text-xs text-slate-500 mt-1">Lat: {{ $addr->latitude }}, Lng: {{ $addr->longitude }}</div>
                        </div>
                        @if($addr->is_default)
                            <span class="text-xs font-semibold text-emerald-400">Default</span>
                        @endif
                    </div>
                @empty
                    <p class="text-slate-500 text-sm">No saved addresses for this customer.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
