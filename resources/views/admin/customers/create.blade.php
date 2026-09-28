@extends('layouts.admin')

@section('title', 'Add New Customer')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Register New Customer</h2>
            <p class="text-xs text-slate-400 mt-1">Create a new customer account and optional primary address details.</p>
        </div>
        <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold border border-slate-700 transition">
            &larr; Back to Registry
        </a>
    </div>

    <!-- Form Panel -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl space-y-6">
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.customers.store') }}" class="space-y-6">
            @csrf

            <!-- Section 1: Customer Personal Info -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">1. Personal & Contact Details</h3>

                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Full Name *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Sarah Jenkins" 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Email Address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. sarah@example.com" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. +1 (555) 345-6789" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Minimum 8 characters" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm Password *</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter password" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>
            </div>

            <!-- Section 2: Address Information -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">2. Primary Address (Optional)</h3>

                <div>
                    <label for="address_line" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Primary Residence Address</label>
                    <textarea id="address_line" name="address_line" rows="3" placeholder="e.g. 104 Green Park Avenue, Sector 5, New Delhi" 
                              class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">{{ old('address_line') }}</textarea>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-800">
                <a href="{{ route('admin.customers.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-sm hover:bg-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                    Create Customer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
