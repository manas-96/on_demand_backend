@extends('layouts.provider')

@section('title', 'Provider Profile & Security Settings')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Provider Account & Security Settings</h1>
            <p class="text-xs text-slate-400">Manage company information, primary contact credentials, and update password.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <!-- Profile & Security Tabs Navigation -->
    <div class="flex items-center space-x-3 border-b border-slate-800 pb-2">
        <a href="{{ route('provider.profile.show', ['tab' => 'profile']) }}" 
           class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeTab === 'profile' ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
            My Profile
        </a>
        <a href="{{ route('provider.profile.show', ['tab' => 'password']) }}" 
           class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeTab === 'password' ? 'bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold shadow-lg shadow-amber-500/20' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
            Change Password
        </a>
    </div>

    @if($activeTab === 'password')
        <!-- Password Form -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl space-y-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">Update Security Password</h2>
            
            <form method="POST" action="{{ route('provider.profile.password') }}" class="space-y-4 max-w-xl">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">New Password *</label>
                    <input type="password" id="password" name="password" required 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm New Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div class="pt-4">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    @else
        <!-- Company & Contact Profile Form -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl space-y-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">Company & Contact Information</h2>
            
            <form method="POST" action="{{ route('provider.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="company_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $providerProfile->company_name ?? '') }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="business_registration" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Business Registration #</label>
                        <input type="text" id="business_registration" name="business_registration" value="{{ old('business_registration', $providerProfile->business_registration ?? '') }}" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Street Address *</label>
                    <textarea id="address" name="address" rows="2" required 
                              class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">{{ old('address', $providerProfile->address ?? '') }}</textarea>
                </div>

                <div>
                    <label for="service_radius_km" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Service Radius (km) *</label>
                    <input type="number" step="0.5" min="1" max="500" id="service_radius_km" name="service_radius_km" value="{{ old('service_radius_km', $providerProfile->service_radius_km ?? 25) }}" required 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div class="border-t border-slate-800 pt-4 space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Primary Contact Person</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Contact Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required 
                                   class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" 
                                   class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Account Email Address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800">
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
