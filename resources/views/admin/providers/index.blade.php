@extends('layouts.admin')

@section('title', 'Service Providers')

@section('content')

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Service Provider Companies</h1>
            <p class="text-xs text-slate-400">Manage service provider onboarding, approval verifications, and radius settings.</p>
        </div>
        <a href="{{ route('admin.providers.create') }}" 
           class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Add Provider</span>
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Interactive Provider Coverage Map Section -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 md:p-5 backdrop-blur-xl space-y-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full bg-amber-400 animate-pulse"></div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Provider Coverage & Location Map</h2>
            </div>
            <span class="text-xs text-slate-400">{{ count($mapProviders) }} Providers Mapped</span>
        </div>

        <div class="relative">
            <input id="map-search-input" type="text" placeholder="🔍 Search city, address, or landmark to pinpoint location on map..." 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition shadow-md">
        </div>

        <div id="provider-map" class="h-96 w-full rounded-xl bg-slate-950 border border-slate-800 overflow-hidden z-10"></div>
    </div>

    <!-- Filter/Search Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
        <form method="GET" action="{{ route('admin.providers.index') }}" class="flex items-center space-x-3 w-full max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search company name or email..." 
                class="w-full px-4 py-2 rounded-lg bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-400">
            <button type="submit" class="px-4 py-2 rounded-lg bg-amber-400 hover:bg-yellow-500 text-slate-950 text-xs font-bold transition">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Company Profile</th>
                    <th class="px-6 py-4">Radius & Commission</th>
                    <th class="px-6 py-4">Technicians</th>
                    <th class="px-6 py-4">Approval</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($providers as $p)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4">
                            <div class="font-bold text-white">{{ $p->company_name }}</div>
                            <div class="text-xs text-slate-400">{{ $p->user->email }} • Reg: {{ $p->business_registration ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            <div>Radius: <strong>{{ $p->service_radius_km }}km</strong></div>
                            <div>Commission: <strong class="text-amber-400">{{ number_format($p->commission ?? 10.00, 2) }}%</strong></div>
                            <div class="text-slate-400 mt-0.5">{{ $p->address ?? 'Address not specified' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                {{ $p->technicians->count() }} Techs
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $p->is_approved ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                {{ $p->is_approved ? 'Approved' : 'Pending' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.providers.show', $p->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200 border border-slate-700">View Details</a>
                            <a href="{{ route('admin.providers.edit', $p->id) }}" class="px-3 py-1.5 rounded-lg bg-amber-400/10 hover:bg-amber-400/20 text-xs font-semibold text-amber-400 border border-amber-400/30">Edit</a>
                            <form method="POST" action="{{ route('admin.providers.toggle-approval', $p->id) }}" class="inline-block">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg {{ $p->is_approved ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }} text-xs font-medium">
                                    {{ $p->is_approved ? 'Revoke Approval' : 'Approve' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No service providers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $providers->withQueryString()->links() }}
        </div>
    </div>
</div>

<!-- Google Maps JS API -->
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4')) }}&callback=initProviderMap&libraries=geometry,places" async defer></script>
<script>
    function initProviderMap() {
        const providers = @json($mapProviders);

        const darkMapStyle = [
            { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
            { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
            { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
            {
                featureType: "administrative.locality",
                elementType: "labels.text.fill",
                stylers: [{ color: "#cbd5e1" }]
            },
            {
                featureType: "poi",
                elementType: "labels.text.fill",
                stylers: [{ color: "#64748b" }]
            },
            {
                featureType: "poi.park",
                elementType: "geometry",
                stylers: [{ color: "#0f172a" }]
            },
            {
                featureType: "road",
                elementType: "geometry",
                stylers: [{ color: "#334155" }]
            },
            {
                featureType: "road",
                elementType: "geometry.stroke",
                stylers: [{ color: "#1e293b" }]
            },
            {
                featureType: "road",
                elementType: "labels.text.fill",
                stylers: [{ color: "#94a3b8" }]
            },
            {
                featureType: "road.highway",
                elementType: "geometry",
                stylers: [{ color: "#475569" }]
            },
            {
                featureType: "water",
                elementType: "geometry",
                stylers: [{ color: "#0f172a" }]
            },
            {
                featureType: "water",
                elementType: "labels.text.fill",
                stylers: [{ color: "#475569" }]
            }
        ];

        // Default map center set to New York (40.7128, -74.0060)
        const nyCenter = { lat: 40.7128, lng: -74.0060 };

        const map = new google.maps.Map(document.getElementById('provider-map'), {
            center: nyCenter,
            zoom: 12,
            styles: darkMapStyle,
            disableDefaultUI: false,
            zoomControl: true,
        });

        const bounds = new google.maps.LatLngBounds();
        const infoWindow = new google.maps.InfoWindow();

        // Connect Google Places Autocomplete to Search Input Bar
        const searchInput = document.getElementById('map-search-input');
        if (searchInput && google.maps.places) {
            const autocomplete = new google.maps.places.Autocomplete(searchInput);
            autocomplete.bindTo('bounds', map);
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) return;
                const searchPos = {
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                };
                map.setCenter(searchPos);
                map.setZoom(14);
            });
        }

        // Render mapped providers
        if (providers.length > 0) {
            let hasValidProviders = false;
            providers.forEach(function(provider) {
                if (provider.lat === null || provider.lng === null) return;

                const pos = { lat: parseFloat(provider.lat), lng: parseFloat(provider.lng) };
                bounds.extend(pos);
                hasValidProviders = true;

                new google.maps.Circle({
                    strokeColor: '#f59e0b',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: '#fbbf24',
                    fillOpacity: 0.15,
                    map: map,
                    center: pos,
                    radius: provider.radius * 1000,
                });

                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    title: provider.name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 9,
                        fillColor: '#f59e0b',
                        fillOpacity: 1,
                        strokeWeight: 3,
                        strokeColor: '#0f172a',
                    },
                });

                marker.addListener('click', function() {
                    const content = `
                        <div style="font-family: system-ui, -apple-system, sans-serif; padding: 6px; max-width: 240px; color: #0f172a;">
                            <div style="font-weight: 700; font-size: 14px; margin-bottom: 2px; color: #0f172a;">${provider.name}</div>
                            <div style="font-size: 12px; color: #475569; margin-bottom: 6px;">${provider.address}</div>
                            <div style="font-size: 11px; font-weight: 700; color: #d97706; margin-bottom: 8px;">Service Radius: ${provider.radius} km</div>
                            <a href="${provider.url}" style="display: inline-block; font-size: 11px; font-weight: 700; background: #0f172a; color: #ffffff; padding: 5px 10px; border-radius: 6px; text-decoration: none;">View Details &rarr;</a>
                        </div>
                    `;
                    infoWindow.setContent(content);
                    infoWindow.open(map, marker);
                });
            });

            if (hasValidProviders) {
                map.fitBounds(bounds);
            }
        }
    }
</script>
@endsection
