@extends('layouts.provider-onboarding')

@section('title', 'Register as Provider')

@php
    $inputClass = 'w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition';
    $labelClass = 'block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2';
    $oldCategories = array_map('intval', (array) old('categories', []));
@endphp

@section('content')
    <div class="space-y-6">
        <div class="text-center space-y-2">
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-lg bg-amber-400/10 text-amber-400 border border-amber-400/20 text-xs font-semibold">
                <span>🤝 Join Our Partner Network</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Register as a Service Provider</h1>
            <p class="text-sm text-slate-400 max-w-xl mx-auto">Create your provider profile. After you verify your email, our team reviews your details and approves your account before you start receiving jobs.</p>
        </div>

        <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-6 md:p-8 shadow-2xl shadow-amber-950/20 backdrop-blur">
            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.provider.post') }}" class="space-y-6" id="provider-registration-form">
                @csrf

                <!-- Section 1: Business Details -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">1. Company & Business Profile</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="company_name" class="{{ $labelClass }}">Company Name *</label>
                            <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" required placeholder="e.g. Apex Home Services" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="business_registration" class="{{ $labelClass }}">Business Registration #</label>
                            <input type="text" id="business_registration" name="business_registration" value="{{ old('business_registration') }}" placeholder="e.g. REG-2026-0099" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Location -->
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">2. Business Location & Service Area</h3>

                    <div>
                        <label for="location_search" class="{{ $labelClass }}">Find your business address</label>
                        <div class="flex gap-2">
                            <input type="text" id="location_search" placeholder="Start typing an address…" autocomplete="off" class="{{ $inputClass }}">
                            <button type="button" id="use-my-location" class="shrink-0 text-xs font-semibold px-3 rounded-xl bg-slate-900 border border-slate-700 text-slate-300 hover:border-amber-400 transition">📍 My location</button>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Search, then drag the pin to your exact location. Jobs are matched by distance from this pin.</p>
                    </div>

                    <div id="registration-map" class="w-full h-64 rounded-xl border border-slate-700 bg-slate-900"></div>
                    <p id="location-status" class="text-xs {{ old('latitude') ? 'text-emerald-400' : 'text-slate-400' }}">
                        {{ old('latitude') ? 'Location pinned.' : 'No location pinned yet.' }}
                    </p>

                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude') }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude') }}">

                    <details class="text-xs text-slate-400" id="manual-coordinates">
                        <summary class="cursor-pointer hover:text-slate-200">Map not loading? Enter coordinates manually</summary>
                        <div class="grid grid-cols-2 gap-3 mt-3">
                            <input type="number" step="any" id="manual_latitude" placeholder="Latitude (e.g. 40.7128)" value="{{ old('latitude') }}" class="{{ $inputClass }}">
                            <input type="number" step="any" id="manual_longitude" placeholder="Longitude (e.g. -74.0060)" value="{{ old('longitude') }}" class="{{ $inputClass }}">
                        </div>
                    </details>

                    <div>
                        <label for="address" class="{{ $labelClass }}">Full Operating Address *</label>
                        <textarea id="address" name="address" rows="2" required placeholder="e.g. 742 Evergreen Terrace, Suite 100" class="{{ $inputClass }}">{{ old('address') }}</textarea>
                    </div>

                    <div>
                        <label for="service_radius_km" class="{{ $labelClass }}">Operating Service Radius (km) *</label>
                        <input type="number" step="0.5" min="1" max="500" id="service_radius_km" name="service_radius_km" value="{{ old('service_radius_km', 25) }}" required class="{{ $inputClass }}">
                    </div>
                </div>

                <!-- Section 3: Contact & Account -->
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">3. Primary Contact & Account Access</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="{{ $labelClass }}">Contact Name *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Robert Smith" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="phone" class="{{ $labelClass }}">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="e.g. +1 (555) 234-5678" class="{{ $inputClass }}">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="{{ $labelClass }}">Login Email Address *</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="e.g. provider@company.com" class="{{ $inputClass }}">
                        <p class="mt-1 text-[11px] text-slate-500">We'll send a 6-digit code to verify this address.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="{{ $labelClass }}">Password *</label>
                            <input type="password" id="password" name="password" required placeholder="Minimum 8 characters" class="{{ $inputClass }}">
                        </div>

                        <div>
                            <label for="password_confirmation" class="{{ $labelClass }}">Confirm Password *</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Re-enter password" class="{{ $inputClass }}">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Offered Service Categories -->
                @if(isset($categories) && $categories->count() > 0)
                    <div class="space-y-4 pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-700/80 pb-2">4. Service Categories Offered *</h3>
                        <p class="text-[11px] text-slate-500 -mt-2">Selecting a main category includes all of its sub-categories. Expand to choose specific ones.</p>

                        <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                            @foreach($categories as $category)
                                @php
                                    $childIds = $category->subcategories->pluck('id')->all();
                                    $parentChecked = in_array($category->id, $oldCategories, true);
                                    $childrenChecked = count(array_intersect($childIds, $oldCategories));
                                @endphp
                                <details class="rounded-xl bg-slate-900 border border-slate-700 group" {{ $childrenChecked && ! $parentChecked ? 'open' : '' }}>
                                    <summary class="flex items-center justify-between p-3 cursor-pointer list-none">
                                        <label class="flex items-center space-x-3 cursor-pointer" onclick="event.stopPropagation()">
                                            <input type="checkbox" name="categories[]" value="{{ $category->id }}" data-parent-checkbox="{{ $category->id }}"
                                                   {{ $parentChecked ? 'checked' : '' }}
                                                   class="w-4 h-4 rounded text-amber-400 bg-slate-950 border-slate-700 focus:ring-amber-400">
                                            @if($category->image)
                                                <img src="{{ Str::startsWith($category->image, ['http://', 'https://']) ? $category->image : asset('storage/'.$category->image) }}" alt="" class="w-8 h-8 object-cover rounded-lg border border-slate-700">
                                            @else
                                                <div class="w-8 h-8 rounded-lg bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-xs font-bold">{{ strtoupper(substr($category->name, 0, 2)) }}</div>
                                            @endif
                                            <span class="text-sm font-semibold text-white">{{ $category->name }}</span>
                                        </label>
                                        @if(count($childIds))
                                            <span class="text-[11px] text-slate-500 group-open:text-amber-400">{{ count($childIds) }} sub-categories ▾</span>
                                        @endif
                                    </summary>

                                    @if(count($childIds))
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 px-3 pb-3">
                                            @foreach($category->subcategories as $sub)
                                                <label class="flex items-center space-x-2 p-2 rounded-lg border border-slate-800 hover:border-amber-400/40 cursor-pointer">
                                                    <input type="checkbox" name="categories[]" value="{{ $sub->id }}" data-child-of="{{ $category->id }}"
                                                           {{ $parentChecked || in_array($sub->id, $oldCategories, true) ? 'checked' : '' }}
                                                           class="w-4 h-4 rounded text-amber-400 bg-slate-950 border-slate-700 focus:ring-amber-400">
                                                    <span class="text-xs text-slate-200">{{ $sub->name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Submit -->
                <div class="pt-4 border-t border-slate-700/80">
                    <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold rounded-xl shadow-lg shadow-amber-500/20 transition duration-200 text-sm">
                        Register Provider Account
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                Already registered as a Provider?
                <a href="{{ route('landing') }}" class="text-amber-400 font-semibold hover:underline">Sign In here</a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const manualLat = document.getElementById('manual_latitude');
        const manualLng = document.getElementById('manual_longitude');
        const statusEl = document.getElementById('location-status');
        let map = null;
        let marker = null;

        function setLocation(lat, lng, label) {
            latInput.value = Number(lat).toFixed(7);
            lngInput.value = Number(lng).toFixed(7);
            manualLat.value = latInput.value;
            manualLng.value = lngInput.value;
            statusEl.textContent = (label || 'Location pinned') + ` (${latInput.value}, ${lngInput.value})`;
            statusEl.className = 'text-xs text-emerald-400';
            if (map && marker) {
                const position = { lat: Number(lat), lng: Number(lng) };
                marker.setPosition(position);
                map.panTo(position);
            }
        }

        [manualLat, manualLng].forEach((input) => input.addEventListener('change', () => {
            if (manualLat.value !== '' && manualLng.value !== '') {
                setLocation(manualLat.value, manualLng.value, 'Location entered manually');
            }
        }));

        document.getElementById('use-my-location').addEventListener('click', () => {
            if (!navigator.geolocation) { return; }
            navigator.geolocation.getCurrentPosition(
                (pos) => { setLocation(pos.coords.latitude, pos.coords.longitude, 'Using your current location'); if (map) { map.setZoom(15); } },
                () => { statusEl.textContent = 'Could not read your location. Search for your address instead.'; }
            );
        });

        // Category tree: a main category checks/unchecks all of its sub-categories.
        document.querySelectorAll('[data-parent-checkbox]').forEach((parent) => {
            parent.addEventListener('change', () => {
                document.querySelectorAll(`[data-child-of="${parent.dataset.parentCheckbox}"]`).forEach((child) => { child.checked = parent.checked; });
            });
        });
        document.querySelectorAll('[data-child-of]').forEach((child) => {
            child.addEventListener('change', () => {
                const parent = document.querySelector(`[data-parent-checkbox="${child.dataset.childOf}"]`);
                const siblings = [...document.querySelectorAll(`[data-child-of="${child.dataset.childOf}"]`)];
                if (parent) { parent.checked = siblings.every((s) => s.checked); }
            });
        });

        document.getElementById('provider-registration-form').addEventListener('submit', (event) => {
            if (!latInput.value || !lngInput.value) {
                event.preventDefault();
                statusEl.textContent = 'Please pin your business location on the map before registering.';
                statusEl.className = 'text-xs text-rose-400';
                statusEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        window.initRegistrationMap = function () {
            const hasOld = latInput.value && lngInput.value;
            const start = hasOld ? { lat: Number(latInput.value), lng: Number(lngInput.value) } : { lat: 39.8283, lng: -98.5795 };

            map = new google.maps.Map(document.getElementById('registration-map'), {
                center: start,
                zoom: hasOld ? 15 : 4,
                mapTypeControl: false,
                streetViewControl: false,
            });
            marker = new google.maps.Marker({ position: start, map, draggable: true });
            marker.addListener('dragend', () => {
                const p = marker.getPosition();
                setLocation(p.lat(), p.lng(), 'Pin moved');
            });
            map.addListener('click', (e) => setLocation(e.latLng.lat(), e.latLng.lng(), 'Pin placed'));

            if (google.maps.places) {
                const autocomplete = new google.maps.places.Autocomplete(document.getElementById('location_search'), { fields: ['geometry', 'formatted_address'] });
                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    if (!place.geometry) { return; }
                    setLocation(place.geometry.location.lat(), place.geometry.location.lng(), 'Address found');
                    map.setZoom(16);
                    const address = document.getElementById('address');
                    if (!address.value.trim() && place.formatted_address) { address.value = place.formatted_address; }
                });
            }
        };

        window.gm_authFailure = function () {
            document.getElementById('manual-coordinates').open = true;
            statusEl.textContent = 'The map could not load. Enter your coordinates manually below.';
        };
    })();
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4')) }}&callback=initRegistrationMap&libraries=places" async defer></script>
@endpush
