@extends('layouts.admin')

@section('title', 'Provider Profile Details')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <a href="{{ route('admin.providers.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">← Back to Providers</a>
            <h1 class="text-2xl font-bold text-white">{{ $providerProfile->company_name }}</h1>
            <p class="text-xs text-slate-400">Account: {{ $providerProfile->user->email }} • Profile ID #{{ $providerProfile->id }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.providers.edit', $providerProfile->id) }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 text-xs font-bold shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                Edit Provider
            </a>
            <form method="POST" action="{{ route('admin.providers.toggle-job-acceptance', $providerProfile->id) }}">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700">
                    {{ $providerProfile->is_accepting_jobs ? 'Pause Job Acceptance' : 'Enable Job Acceptance' }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.providers.toggle-approval', $providerProfile->id) }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl {{ $providerProfile->is_approved ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }} text-xs font-semibold">
                    {{ $providerProfile->is_approved ? 'Revoke Approval' : 'Approve Company' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Top Grid: Company Information (1 Column) + Location & Service Radius Map (2 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Company Metadata -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 lg:col-span-1">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">Company Details</h2>
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-slate-500 text-xs uppercase tracking-wider block">Company Name</span>
                    <span class="text-white font-semibold text-base">{{ $providerProfile->company_name }}</span>
                </div>
                <div>
                    <span class="text-slate-500 text-xs uppercase tracking-wider block">Business Registration</span>
                    <span class="text-slate-200 font-medium">{{ $providerProfile->business_registration ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 text-xs uppercase tracking-wider block">Full Address</span>
                    <span class="text-slate-200 font-medium leading-relaxed block">{{ $providerProfile->address ?? 'Address not specified' }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-800/80">
                    <div>
                        <span class="text-slate-500 text-xs uppercase tracking-wider block">Service Radius</span>
                        <span class="text-white font-bold">{{ $providerProfile->service_radius_km }} km</span>
                    </div>
                    <div>
                        <span class="text-slate-500 text-xs uppercase tracking-wider block">Commission</span>
                        <span class="text-amber-400 font-bold">{{ number_format($providerProfile->commission ?? 10.00, 2) }}%</span>
                    </div>
                </div>
                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="text-slate-500 text-xs uppercase tracking-wider">Job Acceptance</span> 
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $providerProfile->is_accepting_jobs ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                        {{ $providerProfile->is_accepting_jobs ? 'Accepting Jobs' : 'Paused' }}
                    </span>
                </div>
                <div class="pt-2 border-t border-slate-800/80 space-y-1">
                    <span class="text-slate-500 text-xs uppercase tracking-wider block">Account Contact</span>
                    <div class="text-slate-200 font-medium">{{ $providerProfile->user->name }}</div>
                    <div class="text-xs text-slate-400">{{ $providerProfile->user->email }}</div>
                    @if($providerProfile->user->phone)
                        <div class="text-xs text-slate-400">{{ $providerProfile->user->phone }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Prominent Location & Service Radius Map (2 Columns) -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 lg:col-span-2 flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Location & Operating Coverage</h2>
                <span class="text-xs text-slate-400">Radius: <strong class="text-white">{{ $providerProfile->service_radius_km }} km</strong></span>
            </div>
            <div id="single-provider-map" class="h-[380px] w-full rounded-xl bg-slate-950 border border-slate-800 overflow-hidden flex-1"></div>
        </div>
    </div>

    <!-- Offered Service Categories (Parent-Wise & 3 Columns) -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
        <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Offered Service Categories</h2>
            @php
                $offeredCategories = $providerProfile->providerServices
                    ->map(fn($ps) => $ps->service->category ?? null)
                    ->filter()
                    ->unique('id');

                $groupedCategories = $offeredCategories->groupBy(function($cat) {
                    return $cat->parent ? $cat->parent->name : ($cat->subcategories->count() > 0 ? $cat->name : 'Primary Categories');
                });
            @endphp
            <span class="text-xs text-slate-400">Total Configured: <strong class="text-white">{{ $offeredCategories->filter(fn($c) => $c->parent_id !== null || $c->subcategories->count() == 0)->count() }}</strong></span>
        </div>

        @if($groupedCategories->count() > 0)
            <div class="space-y-4">
                @foreach($groupedCategories as $parentName => $categoryList)
                    @php
                        $subItems = $categoryList->filter(fn($c) => $c->parent_id !== null || $c->subcategories->count() == 0);
                    @endphp
                    @if($subItems->count() > 0)
                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-5 space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                                <div class="flex items-center space-x-2 text-xs font-bold text-amber-400 uppercase tracking-wider">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                    </svg>
                                    <span>Parent Category: {{ $parentName }}</span>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-400/10 text-amber-400 text-xs font-semibold">
                                    {{ $subItems->count() }} Offered
                                </span>
                            </div>

                            <!-- 3 Columns Subcategories Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                                @foreach($subItems as $cat)
                                    <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800/80 flex items-center space-x-3 hover:border-amber-400/40 transition">
                                        @if($cat->image)
                                            @if(Str::startsWith($cat->image, ['http://', 'https://']))
                                                <img src="{{ $cat->image }}" alt="{{ $cat->name }}" class="w-8 h-8 object-cover rounded-lg border border-slate-700 shrink-0">
                                            @else
                                                <img src="{{ asset('storage/' . $cat->image) }}" alt="{{ $cat->name }}" class="w-8 h-8 object-cover rounded-lg border border-slate-700 shrink-0">
                                            @endif
                                        @else
                                            <div class="w-8 h-8 rounded-lg bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-xs font-bold shrink-0">
                                                {{ strtoupper(substr($cat->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <span class="text-xs font-semibold text-white block truncate">{{ $cat->name }}</span>
                                            @if($cat->parent)
                                                <span class="text-[10px] text-amber-400/80 font-medium">Subcategory of {{ $cat->parent->name }}</span>
                                            @else
                                                <span class="text-[10px] text-slate-400 font-medium">Primary Category</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-slate-500 text-sm italic py-4">No service categories configured for this provider.</p>
        @endif
    </div>

    <!-- Employed Technicians -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">Employed Technicians</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($providerProfile->technicians as $tech)
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-white text-sm">{{ $tech->user->name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $tech->qualification ?? 'General Field Technician' }} • {{ $tech->user->email }}</div>
                    </div>
                    <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        {{ ucfirst(str_replace('_', ' ', $tech->duty_status)) }}
                    </span>
                </div>
            @empty
                <p class="text-slate-500 text-sm col-span-2 italic">No technicians linked to this provider profile.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Google Maps JS API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4')) }}&callback=initSingleProviderMap&libraries=geometry" async defer></script>
<script>
    function initSingleProviderMap() {
        const hasProviderCoords = {{ $providerProfile->latitude !== null && $providerProfile->longitude !== null ? 'true' : 'false' }};
        let lat = {{ (float) ($providerProfile->latitude ?? 20.5937) }};
        let lng = {{ (float) ($providerProfile->longitude ?? 78.9629) }};
        const radiusKm = {{ (float) ($providerProfile->service_radius_km ?? 25.0) }};
        const companyName = @json($providerProfile->company_name);

        const darkMapStyle = [
            { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
            { featureType: "road", elementType: "geometry", stylers: [{ color: "#334155" }] },
            { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] }
        ];

        const map = new google.maps.Map(document.getElementById('single-provider-map'), {
            center: { lat: lat, lng: lng },
            zoom: 12,
            styles: darkMapStyle,
            disableDefaultUI: true,
            zoomControl: true,
        });

        if (hasProviderCoords) {
            const pos = { lat: lat, lng: lng };

            new google.maps.Circle({
                strokeColor: '#f59e0b',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#fbbf24',
                fillOpacity: 0.15,
                map: map,
                center: pos,
                radius: radiusKm * 1000,
            });

            new google.maps.Marker({
                position: pos,
                map: map,
                title: companyName,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 9,
                    fillColor: '#f59e0b',
                    fillOpacity: 1,
                    strokeWeight: 3,
                    strokeColor: '#0f172a',
                },
            });
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const userPos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };

                    new google.maps.Marker({
                        position: userPos,
                        map: map,
                        title: "Your Current Location",
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 8,
                            fillColor: '#3b82f6',
                            fillOpacity: 1,
                            strokeWeight: 3,
                            strokeColor: '#ffffff',
                        },
                        zIndex: 999
                    });

                    if (!hasProviderCoords) {
                        map.setCenter(userPos);
                        map.setZoom(13);
                    }
                },
                function(error) {
                    console.warn("Geolocation warning:", error.message);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }
    }
</script>
@endsection
