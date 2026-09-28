@extends('layouts.admin')

@section('title', 'Edit Service Provider')

@section('content')
<style>
    .pac-container {
        background-color: #0f172a !important;
        border: 1px solid #334155 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        font-family: inherit !important;
        margin-top: 4px !important;
        z-index: 9999 !important;
    }
    .pac-item {
        border-top: 1px solid #1e293b !important;
        padding: 10px 14px !important;
        color: #94a3b8 !important;
        font-size: 13px !important;
        cursor: pointer !important;
    }
    .pac-item:hover, .pac-item-selected {
        background-color: #1e293b !important;
        color: #f59e0b !important;
    }
    .pac-item-query {
        color: #f8fafc !important;
        font-weight: 600 !important;
    }
    .pac-matched {
        color: #fbbf24 !important;
    }
    .pac-icon {
        display: none !important;
    }
</style>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Edit Service Provider</h2>
            <p class="text-xs text-slate-400 mt-1">Update profile information, contact details, and offered service categories for {{ $providerProfile->company_name }}.</p>
        </div>
        <a href="{{ route('admin.providers.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold border border-slate-700 transition">
            &larr; Back to Providers
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

        <form method="POST" action="{{ route('admin.providers.update', $providerProfile->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Company Information -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">1. Company & Business Details</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="company_name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $providerProfile->company_name) }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="business_registration" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Business Registration #</label>
                        <input type="text" id="business_registration" name="business_registration" value="{{ old('business_registration', $providerProfile->business_registration) }}" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>

                <!-- Google Places Autocomplete Address Field -->
                <div>
                    <label for="address" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Full Street Address * (Google Places Autocomplete)</label>
                    <div class="relative">
                        <input type="text" id="address" name="address" value="{{ old('address', $providerProfile->address) }}" required 
                               placeholder="Type street address or business location for Google suggestions..." 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition"
                               autocomplete="off">
                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $providerProfile->latitude) }}">
                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $providerProfile->longitude) }}">
                        <div id="address-suggestions" class="hidden absolute left-0 right-0 top-full mt-1 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl z-50 max-h-60 overflow-y-auto"></div>
                    </div>
                    <div class="mt-2 text-xs flex items-center justify-end text-slate-400">
                        <span class="text-slate-500 italic">Start typing for Google location suggestions</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="service_radius_km" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Service Radius (km) *</label>
                        <input type="number" step="0.5" min="1" max="500" id="service_radius_km" name="service_radius_km" value="{{ old('service_radius_km', $providerProfile->service_radius_km) }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="commission" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Platform Commission Rate (%) *</label>
                        <input type="number" step="0.01" min="0" max="100" id="commission" name="commission" value="{{ old('commission', $providerProfile->commission) }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>
            </div>

            <!-- Section 2: Contact Person & User Credentials -->
            <div class="space-y-4 pt-2">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">2. Contact Person & Account Access</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Contact Name *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $providerProfile->user->name) }}" required 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $providerProfile->user->phone) }}" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Login Email Address *</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $providerProfile->user->email) }}" required 
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">New Password (Optional)</label>
                        <input type="password" id="password" name="password" placeholder="Leave blank to keep existing password" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Confirm New Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Re-enter new password" 
                               class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    </div>
                </div>
            </div>

            <!-- Section 3: Service Categories Offered (Clickable Parent Categories) -->
            @if(isset($categories) && $categories->count() > 0)
                <div class="space-y-4 pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 border-b border-slate-800 pb-2">3. Service Categories Offered</h3>
                    <p class="text-xs text-slate-400">Click on any parent service category below to view and select its offered subcategories.</p>

                    <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                        @foreach($categories as $parent)
                            @php
                                $subcategories = $parent->subcategories;
                                $hasSubs = $subcategories->count() > 0;
                                $selectedIds = old('categories', $offeredCategoryIds);
                                $checkedCount = $subcategories->filter(fn($sub) => in_array($sub->id, $selectedIds))->count();
                            @endphp
                            <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl overflow-hidden transition">
                                <!-- Clickable Parent Category Header -->
                                <button type="button" 
                                        onclick="toggleCategoryAccordion('parent-cat-{{ $parent->id }}')" 
                                        class="w-full px-5 py-3.5 flex items-center justify-between bg-slate-900/90 hover:bg-slate-800/90 transition text-left focus:outline-none group">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        <div class="w-9 h-9 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 font-bold shrink-0">
                                            📁
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-white group-hover:text-amber-400 transition truncate">{{ $parent->name }}</div>
                                            <div class="text-xs text-slate-400 font-medium flex items-center space-x-2 mt-0.5">
                                                <span>{{ $hasSubs ? $subcategories->count() . ' Subcategories' : 'Primary Category' }}</span>
                                                <span id="selected-badge-{{ $parent->id }}" class="px-2 py-0.5 rounded-full bg-amber-400/20 text-amber-400 text-[10px] font-bold {{ $checkedCount > 0 ? '' : 'hidden' }}">
                                                    <span id="selected-count-{{ $parent->id }}">{{ $checkedCount }}</span> Selected
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 text-slate-400 shrink-0">
                                        <span class="text-xs font-semibold text-amber-400/80 hidden sm:inline">Click to view</span>
                                        <svg id="chevron-{{ $parent->id }}" class="w-5 h-5 transform transition-transform duration-200 {{ $checkedCount > 0 ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </button>

                                <!-- Subcategories Container -->
                                <div id="parent-cat-{{ $parent->id }}" class="{{ $checkedCount > 0 ? '' : 'hidden' }} p-4 border-t border-slate-800/60 bg-slate-950/40 space-y-3">
                                    @if($hasSubs)
                                        <div class="flex items-center justify-between pb-2 text-xs text-slate-400 border-b border-slate-800/40">
                                            <span>Select available services under <strong>{{ $parent->name }}</strong>:</span>
                                            <button type="button" onclick="toggleSelectAllParent('{{ $parent->id }}')" class="text-amber-400 hover:text-amber-300 font-semibold text-xs transition">
                                                Toggle Select All
                                            </button>
                                        </div>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                            @foreach($subcategories as $category)
                                                @php
                                                    $isChecked = in_array($category->id, $selectedIds);
                                                @endphp
                                                <label class="flex items-center space-x-3 p-3 rounded-xl bg-slate-900 border border-slate-800/80 cursor-pointer hover:border-amber-400/50 transition">
                                                    <input type="checkbox" 
                                                           name="categories[]" 
                                                           value="{{ $category->id }}" 
                                                           data-parent-id="{{ $parent->id }}"
                                                           onchange="updateParentBadge('{{ $parent->id }}')"
                                                           {{ $isChecked ? 'checked' : '' }}
                                                           class="category-checkbox-{{ $parent->id }} w-4 h-4 rounded text-amber-400 bg-slate-950 border-slate-700 focus:ring-amber-400">
                                                    <div class="flex items-center space-x-2.5 min-w-0 flex-1">
                                                        @if($category->image)
                                                            @if(Str::startsWith($category->image, ['http://', 'https://']))
                                                                <img src="{{ $category->image }}" alt="{{ $category->name }}" class="w-7 h-7 object-cover rounded-lg border border-slate-700 shrink-0">
                                                            @else
                                                                <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name }}" class="w-7 h-7 object-cover rounded-lg border border-slate-700 shrink-0">
                                                            @endif
                                                        @else
                                                            <div class="w-7 h-7 rounded-lg bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-xs font-bold shrink-0">
                                                                {{ strtoupper(substr($category->name, 0, 2)) }}
                                                            </div>
                                                        @endif
                                                        <div class="min-w-0 flex-1">
                                                            <div class="text-xs font-semibold text-white truncate">{{ $category->name }}</div>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        @php
                                            $isChecked = in_array($parent->id, $selectedIds);
                                        @endphp
                                        <label class="flex items-center space-x-3 p-3 rounded-xl bg-slate-900 border border-slate-800/80 cursor-pointer hover:border-amber-400/50 transition">
                                            <input type="checkbox" 
                                                   name="categories[]" 
                                                   value="{{ $parent->id }}" 
                                                   data-parent-id="{{ $parent->id }}"
                                                   onchange="updateParentBadge('{{ $parent->id }}')"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   class="category-checkbox-{{ $parent->id }} w-4 h-4 rounded text-amber-400 bg-slate-950 border-slate-700 focus:ring-amber-400">
                                            <span class="text-xs font-semibold text-white">Select {{ $parent->name }}</span>
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Submit buttons -->
            <div class="pt-4 flex items-center justify-end space-x-3 border-t border-slate-800">
                <a href="{{ route('admin.providers.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-sm hover:bg-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                    Update Provider
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const addressInput = document.getElementById('address');
        const suggestionsBox = document.getElementById('address-suggestions');
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        if (!addressInput || !suggestionsBox) return;

        let debounceTimer = null;

        addressInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                suggestionsBox.classList.add('hidden');
                suggestionsBox.innerHTML = '';
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetch(`{{ route('admin.places.autocomplete') }}?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data) && data.length > 0) {
                            renderSuggestions(data);
                        } else {
                            suggestionsBox.classList.add('hidden');
                            suggestionsBox.innerHTML = '';
                        }
                    })
                    .catch(err => {
                        console.error('Place autocomplete error:', err);
                        suggestionsBox.classList.add('hidden');
                    });
            }, 300);
        });

        function renderSuggestions(items) {
            suggestionsBox.innerHTML = '';
            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'px-4 py-2.5 text-xs text-slate-200 hover:bg-amber-400/10 hover:text-amber-400 cursor-pointer border-b border-slate-800/50 last:border-0 flex items-start space-x-2 transition';
                
                const mainText = item.main_text || item.description;
                const secondaryText = item.secondary_text ? `<span class="block text-[11px] text-slate-400 font-normal mt-0.5">${item.secondary_text}</span>` : '';

                div.innerHTML = `
                    <span class="text-amber-400 font-bold shrink-0 mt-0.5">📍</span>
                    <div class="overflow-hidden">
                        <span class="font-semibold text-slate-200 block truncate">${mainText}</span>
                        ${secondaryText}
                    </div>
                `;

                div.addEventListener('click', function() {
                    addressInput.value = item.description || item.main_text;
                    
                    if (item.lat !== undefined && item.lat !== null) {
                        setCoordinates(item.lat, item.lng);
                        suggestionsBox.classList.add('hidden');
                        return;
                    }

                    if (item.place_id) {
                        fetch(`{{ route('admin.places.details') }}?place_id=${encodeURIComponent(item.place_id)}`)
                            .then(res => res.json())
                            .then(details => {
                                if (details.formatted_address) {
                                    addressInput.value = details.formatted_address;
                                }
                                if (details.lat && details.lng) {
                                    setCoordinates(details.lat, details.lng);
                                }
                            })
                            .catch(err => console.error('Details fetch error:', err));
                    }
                    suggestionsBox.classList.add('hidden');
                });
                suggestionsBox.appendChild(div);
            });
            suggestionsBox.classList.remove('hidden');
        }

        function setCoordinates(lat, lng) {
            latInput.value = parseFloat(lat);
            lngInput.value = parseFloat(lng);
        }

        document.addEventListener('click', function(e) {
            if (!addressInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                suggestionsBox.classList.add('hidden');
            }
        });
    });

    function toggleCategoryAccordion(containerId) {
        const el = document.getElementById(containerId);
        if (!el) return;

        const parentId = containerId.replace('parent-cat-', '');
        const chevron = document.getElementById('chevron-' + parentId);

        el.classList.toggle('hidden');
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

    function updateParentBadge(parentId) {
        const checkboxes = document.querySelectorAll(`.category-checkbox-${parentId}`);
        const badge = document.getElementById(`selected-badge-${parentId}`);
        const countSpan = document.getElementById(`selected-count-${parentId}`);
        
        let checkedCount = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) checkedCount++;
        });

        if (countSpan) countSpan.textContent = checkedCount;
        if (badge) {
            if (checkedCount > 0) {
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    function toggleSelectAllParent(parentId) {
        const checkboxes = document.querySelectorAll(`.category-checkbox-${parentId}`);
        let allChecked = true;
        checkboxes.forEach(cb => {
            if (!cb.checked) allChecked = false;
        });

        checkboxes.forEach(cb => {
            cb.checked = !allChecked;
        });

        updateParentBadge(parentId);
    }
</script>
@endsection
