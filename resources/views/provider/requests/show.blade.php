@extends('layouts.provider')

@section('title', 'Job Request Details')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Request {{ $serviceRequest->request_number }}</h2>
            <p class="text-xs text-slate-400 mt-1">Detailed view of service request and technician assignment state.</p>
        </div>
        <div class="flex items-center space-x-3">
            @php
                $statusVal = is_object($serviceRequest->status) ? $serviceRequest->status->value : $serviceRequest->status;
                $assignmentStatus = $assignment && is_object($assignment->status) ? $assignment->status->value : ($assignment->status ?? '');
                $isCompleted = ($statusVal === 'completed' || $assignmentStatus === 'completed');
                $latestInvoice = $serviceRequest->invoices()->latest()->first();
                $isPaid = ($latestInvoice && $latestInvoice->status === \App\Enums\InvoiceStatus::Paid) || ($serviceRequest->invoices()->where('status', \App\Enums\InvoiceStatus::Paid)->exists());
            @endphp
            @if($isPaid)
                <a href="{{ route('provider.requests.invoice.download', $jobRequest->id ?? $serviceRequest->id) }}" target="_blank" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-sm font-bold transition shadow-md flex items-center space-x-1.5">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Download Final Invoice 📄</span>
                </a>
            @endif
            <a href="{{ route('provider.requests.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold border border-slate-700 transition">
                &larr; Back to Requests
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Request Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Service & Description -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Service Overview</h3>
            <div>
                <div class="text-lg font-bold text-amber-400">{{ $serviceRequest->category->name ?? $serviceRequest->service->name ?? 'General Service' }}</div>
                <div class="text-xs font-semibold text-slate-400 mt-1 flex items-center space-x-1.5">
                    <span class="text-slate-500">Sub Category:</span>
                    <span class="text-slate-200 font-medium">{{ $serviceRequest->subCategory->name ?? 'N/A' }}</span>
                </div>
                <p class="text-sm text-slate-300 mt-3">{{ $serviceRequest->description }}</p>
            </div>

            <div class="pt-4 border-t border-slate-800/80 grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-500 block mb-1">Preferred Date</span>
                    <span class="text-slate-200 font-semibold">{{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block mb-1">Priority</span>
                    <span class="text-slate-200 font-semibold capitalize">{{ $serviceRequest->priority ?? 'Normal' }}</span>
                </div>
            </div>
        </div>

        <!-- Location & Customer Info -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Location & Contact</h3>
            <div>
                <div class="text-xs text-slate-500 mb-1">Service Address</div>
                <div class="text-sm text-slate-200 font-medium">{{ $serviceRequest->address_line }}</div>
                <div class="text-xs text-slate-500 mt-1">GPS: {{ $serviceRequest->latitude }}, {{ $serviceRequest->longitude }}</div>
            </div>
            @if($serviceRequest->customer)
                <div class="pt-4 border-t border-slate-800/80">
                    <div class="text-xs text-slate-500 mb-1">Requested By Customer</div>
                    <div class="text-sm text-white font-semibold">{{ $serviceRequest->customer->name }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Customer Attached Problem Photos -->
    @if($serviceRequest->photos && $serviceRequest->photos->count())
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Customer Attached Problem Photos</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($serviceRequest->photos as $photo)
                    @php
                        $cleanPath = ltrim($photo->file_path, '/');
                        if (str_starts_with($cleanPath, 'storage/')) {
                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'storage/');
                        } elseif (str_starts_with($cleanPath, 'public/')) {
                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'public/');
                        }
                        $imgSrc = str_starts_with($photo->file_path, 'http://') || str_starts_with($photo->file_path, 'https://')
                            ? $photo->file_path
                            : asset('storage/' . $cleanPath);
                    @endphp
                    <div class="group relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 aspect-video flex items-center justify-center">
                        <img src="{{ $imgSrc }}" alt="Problem Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                        <a href="{{ $imgSrc }}" target="_blank" class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold">
                            View Photo ↗
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

    <!-- Actions Grid: Accept Request & Technician Assignment -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Step 1: Direct Job Request Acceptance -->
        @php
            $reqStatusVal = is_object($jobRequest->status) ? $jobRequest->status->value : $jobRequest->status;
            $isAccepted = in_array($reqStatusVal, ['selected', 'accepted']) || in_array($serviceRequest->status->value ?? $serviceRequest->status, ['awarded', 'assigned', 'in_progress', 'completed']);
        @endphp

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4 flex flex-col justify-between">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">1. Accept Job Request</h3>
                <p class="text-xs text-slate-400">Accept this incoming service request to confirm your company availability.</p>
            </div>

            @if($isAccepted)
                <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 space-y-1">
                    <div class="text-xs text-emerald-400 font-bold uppercase">Status: Request Accepted</div>
                    <div class="text-xs text-slate-300">You have accepted this job. Proceed to assign a technician below.</div>
                </div>
            @else
                <form action="{{ route('provider.requests.accept', $jobRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-sm transition shadow-lg flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Accept Job Request</span>
                    </button>
                </form>
            @endif
        </div>

        <!-- Step 2: Technician Assignment -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">2. Technician Assignment</h3>

            @if($assignment && $assignment->technician)
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2">
                    <div class="text-xs text-slate-400 font-semibold">Currently Assigned Technician</div>
                    <div class="text-base font-bold text-white">{{ $assignment->technician->name }}</div>
                    <div class="text-xs text-slate-400">Email: {{ $assignment->technician->email }}</div>
                    <div class="text-xs text-amber-400 font-semibold uppercase">Status: {{ is_object($assignment->status) ? $assignment->status->value : $assignment->status }}</div>
                </div>
            @endif

            <form action="{{ route('provider.requests.assign-technician', $jobRequest->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs text-slate-400 font-semibold mb-1">Select Technician</label>
                    <select name="technician_id" required class="w-full bg-slate-950/80 border border-slate-800 rounded-xl px-4 py-2 text-sm text-white focus:outline-none focus:border-amber-400">
                        <option value="">-- Choose Active Technician --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->user_id }}" {{ ($assignment->technician_id ?? null) == $tech->user_id ? 'selected' : '' }}>
                                {{ $tech->user->name ?? 'Technician' }} ({{ $tech->user->email ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm border border-slate-700 transition">
                    {{ $assignment ? 'Re-assign Technician' : 'Assign Technician' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Technician Live Location Map Section (Visible when Technician is Assigned and Job is Active) -->
    @if($assignment && $assignment->technician && ! $isCompleted)
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4 mt-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center space-x-2.5">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-white tracking-tight flex items-center space-x-2">
                            <span>Technician Live Location & Navigation</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">Live</span>
                        </h3>
                        <p class="text-xs text-slate-400">Real-time GPS tracking for assigned technician <strong class="text-amber-400">{{ $assignment->technician->name }}</strong></p>
                    </div>
                </div>
                <div class="text-xs text-slate-400 flex items-center space-x-3 bg-slate-950/80 border border-slate-800 px-3 py-1.5 rounded-xl">
                    <span class="text-slate-500">Status:</span>
                    <span class="text-amber-400 font-semibold uppercase" id="tech-duty-status">{{ is_object($assignment->status) ? $assignment->status->value : $assignment->status }}</span>
                    <span class="text-slate-700">•</span>
                    <span class="text-slate-400" id="tech-last-ping">Updating live...</span>
                </div>
            </div>

            <!-- Leaflet Map Canvas -->
            <div id="tech-live-map" class="w-full h-80 rounded-xl border border-slate-800/80 bg-slate-950 overflow-hidden relative shadow-inner"></div>

            <!-- Distance & Info Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 text-xs">
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-3 flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-base">👨‍🔧</div>
                    <div>
                        <div class="text-slate-500 text-[10px] uppercase font-bold">Assigned Technician</div>
                        <div class="text-white font-semibold" id="tech-card-name">{{ $assignment->technician->name }}</div>
                    </div>
                </div>
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-3 flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-base">📍</div>
                    <div>
                        <div class="text-slate-500 text-[10px] uppercase font-bold">Service Destination</div>
                        <div class="text-slate-200 font-medium truncate max-w-[200px]">{{ $serviceRequest->address_line }}</div>
                    </div>
                </div>
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-3 flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-400 text-base">🚚</div>
                    <div>
                        <div class="text-slate-500 text-[10px] uppercase font-bold">Estimated Distance & ETA</div>
                        <div class="text-blue-400 font-bold" id="tech-distance-eta">Calculating distance...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaflet Map CSS & JS + Firebase JS SDK -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
        <script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>

        <style>
            .leaflet-div-icon.custom-tech-marker,
            .leaflet-div-icon.custom-dest-marker {
                background: transparent !important;
                border: none !important;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const mapContainer = document.getElementById('tech-live-map');
                if (!mapContainer) return;

                const destLat = parseFloat("{{ $serviceRequest->latitude ?? 12.9800 }}") || 12.9800;
                const destLng = parseFloat("{{ $serviceRequest->longitude ?? 77.6000 }}") || 77.6000;
                const techName = "{{ $assignment->technician->name }}";
                const techUserId = "{{ $assignment->technician_id }}";
                const destAddress = "{{ addslashes($serviceRequest->address_line) }}";

                let map, techMarker, destMarker, polyline;

                function calcDistanceKm(lat1, lon1, lat2, lon2) {
                    const R = 6371;
                    const dLat = (lat2 - lat1) * Math.PI / 180;
                    const dLon = (lon2 - lon1) * Math.PI / 180;
                    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                              Math.sin(dLon/2) * Math.sin(dLon/2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    return R * c;
                }

                function initOrUpdateMap(techLat, techLng, lastUpdatedStr) {
                    const techLatLng = [techLat, techLng];
                    const destLatLng = [destLat, destLng];

                    if (!map) {
                        map = L.map('tech-live-map').setView(techLatLng, 13);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);

                        const techIcon = L.divIcon({
                            className: 'custom-tech-marker',
                            html: `<div style="background-color:#2563eb; color:#ffffff; width:36px; height:36px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 4px 14px rgba(37,99,235,0.9); display:flex; align-items:center; justify-content:center; font-size:18px;">🚚</div>`,
                            iconSize: [36, 36],
                            iconAnchor: [18, 18]
                        });

                        const destIcon = L.divIcon({
                            className: 'custom-dest-marker',
                            html: `<div style="background-color:#dc2626; color:#ffffff; width:34px; height:34px; border-radius:50%; border:3px solid #ffffff; box-shadow:0 4px 12px rgba(220,38,38,0.9); display:flex; align-items:center; justify-content:center; font-size:16px;">📍</div>`,
                            iconSize: [34, 34],
                            iconAnchor: [17, 17]
                        });

                        techMarker = L.marker(techLatLng, { icon: techIcon, zIndexOffset: 1000 }).addTo(map)
                            .bindPopup(`<b>👨‍🔧 ${techName}</b><br>Live Technician Location`);

                        destMarker = L.marker(destLatLng, { icon: destIcon }).addTo(map)
                            .bindPopup(`<b>📍 Service Location</b><br>${destAddress}`);

                        polyline = L.polyline([techLatLng, destLatLng], {
                            color: '#f59e0b',
                            weight: 4,
                            dashArray: '8, 8',
                            opacity: 0.85
                        }).addTo(map);

                        const bounds = L.latLngBounds([techLatLng, destLatLng]);
                        map.fitBounds(bounds, { padding: [50, 50] });

                        setTimeout(() => {
                            if (map) map.invalidateSize();
                        }, 300);
                    } else {
                        techMarker.setLatLng(techLatLng);
                        destMarker.setLatLng(destLatLng);
                        polyline.setLatLngs([techLatLng, destLatLng]);
                        techMarker.bringToFront();
                    }

                    // Update UI details
                    const distKm = calcDistanceKm(techLat, techLng, destLat, destLng);
                    const etaMins = Math.round((distKm / 30) * 60);
                    const distText = distKm < 0.2 ? 'Arrived at Location' : `${distKm.toFixed(1)} km (~${etaMins} mins away)`;

                    const etaEl = document.getElementById('tech-distance-eta');
                    if (etaEl) etaEl.textContent = distText;

                    const pingEl = document.getElementById('tech-last-ping');
                    if (pingEl) pingEl.textContent = `Firebase Live: ${lastUpdatedStr}`;
                }

                // 1. Initialize Map with Default / Destination Pin
                const defaultTechLat = destLat - 0.012;
                const defaultTechLng = destLng - 0.009;
                initOrUpdateMap(defaultTechLat, defaultTechLng, 'Connecting...');

                // 2. Initialize Firebase Web SDK
                const firebaseConfig = {
                    apiKey: "{{ config('services.firebase.api_key') }}",
                    authDomain: "{{ config('services.firebase.project_id') }}.firebaseapp.com",
                    projectId: "{{ config('services.firebase.project_id') }}",
                    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
                    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
                    appId: "{{ config('services.firebase.app_id') }}"
                };

                if (!firebase.apps.length) {
                    firebase.initializeApp(firebaseConfig);
                }
                const db = firebase.firestore();

                function handleLocationDoc(doc) {
                    if (doc && doc.exists) {
                        const data = doc.data();
                        const lat = parseFloat(data.lat || data.latitude);
                        const lng = parseFloat(data.long || data.longitude);

                        if (!isNaN(lat) && !isNaN(lng)) {
                            let formattedTime = 'Just now';
                            if (data.updated_at && typeof data.updated_at.toDate === 'function') {
                                formattedTime = data.updated_at.toDate().toLocaleTimeString();
                            }
                            initOrUpdateMap(lat, lng, formattedTime);
                            return true;
                        }
                    }
                    return false;
                }

                // 3. Listen to Firestore technician_location live stream
                db.collection('technician_location').doc(techUserId).onSnapshot((doc) => {
                    if (!handleLocationDoc(doc)) {
                        db.collection('technician_location').doc('1').onSnapshot((fDoc) => {
                            if (!handleLocationDoc(fDoc)) {
                                db.collection('technician_location').limit(1).get().then((snap) => {
                                    if (!snap.empty) {
                                        handleLocationDoc(snap.docs[0]);
                                    }
                                }).catch(() => {});
                            }
                        });
                    }
                }, (error) => {
                    console.warn('⚠️ Firebase Firestore location stream notice:', error);
                });
            });
        </script>
    @endif

    <!-- Payment & Settlement Overview Section -->
    @if($isCompleted || $latestInvoice)
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4 mt-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-sm font-bold text-white tracking-tight flex items-center space-x-2">
                        <span>Payment & Settlement Overview 💳</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Financial breakdown and invoice settlement status for this service request.</p>
                </div>
                <div>
                    @if($isPaid)
                        <span class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span>Full Payment Done ✓</span>
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold uppercase flex items-center space-x-1">
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Payment Pending Settlement ⏳</span>
                        </span>
                    @endif
                </div>
            </div>

            @php
                $effectiveQuote = $quote ?? $serviceRequest->commercialQuote;
                $quoteTotal = (float) ($effectiveQuote->total ?? $latestInvoice?->subtotal ?? $serviceRequest->service?->base_price ?? 0.00);
                $netBalanceDue = max(0, $quoteTotal - 250.00);
                $tipAmount = (float) ($latestInvoice?->tip_amount ?? 0.00);
                $gridCols = $tipAmount > 0 ? 'sm:grid-cols-5' : 'sm:grid-cols-4';
            @endphp
            <div class="grid grid-cols-1 {{ $gridCols }} gap-4 text-xs pt-2">
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 space-y-1">
                    <span class="text-slate-500 font-semibold block">Total Service Amount</span>
                    <span class="text-white font-bold text-base">${{ number_format($quoteTotal, 2) }}</span>
                </div>
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 space-y-1">
                    <span class="text-slate-500 font-semibold block">Upfront Deposit Credit</span>
                    <span class="text-emerald-400 font-bold text-base">-$250.00</span>
                </div>
                @if($tipAmount > 0)
                    <div class="bg-indigo-950/40 border border-indigo-500/30 rounded-xl p-4 space-y-1">
                        <span class="text-indigo-400 font-semibold block flex items-center space-x-1">
                            <span>Technician Tip 🎁</span>
                        </span>
                        <span class="text-indigo-300 font-bold text-base">+${{ number_format($tipAmount, 2) }}</span>
                    </div>
                @endif
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 space-y-1">
                    <span class="text-slate-500 font-semibold block">Final Settlement Paid</span>
                    <span class="{{ $isPaid ? 'text-emerald-400' : 'text-slate-400' }} font-bold text-base">
                        ${{ $isPaid ? number_format($netBalanceDue + $tipAmount, 2) : '0.00' }}
                    </span>
                </div>
                <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-4 space-y-1">
                    <span class="text-slate-500 font-semibold block">Remaining Balance Due</span>
                    <span class="{{ $isPaid ? 'text-emerald-400' : 'text-amber-400' }} font-bold text-base">
                        ${{ $isPaid ? '0.00 (Fully Settled)' : number_format($netBalanceDue, 2) }}
                    </span>
                </div>
            </div>

            @if($isPaid)
                @php
                    $providerProfile = auth()->user()?->providerProfile ?? \App\Models\ProviderProfile::where('user_id', auth()->id())->first();
                    $commissionRate = (float) ($providerProfile?->commission ?? 10.00);

                    $effectiveQuote = $quote ?? $serviceRequest->commercialQuote;
                    $quoteLabor = (float) ($effectiveQuote?->labor_subtotal ?? 0.00);
                    $quoteMaterial = (float) ($effectiveQuote?->material_subtotal ?? 0.00);
                    $quotePreTaxSubtotal = $quoteLabor + $quoteMaterial;

                    if ($quotePreTaxSubtotal > 0) {
                        $serviceCost = $quotePreTaxSubtotal;
                    } elseif ($effectiveQuote && (float) $effectiveQuote->subtotal > 0) {
                        $serviceCost = (float) $effectiveQuote->subtotal;
                    } else {
                        $serviceCost = (float) ($serviceRequest->service?->base_price ?? $latestInvoice?->subtotal ?? 0.00);
                    }

                    $taxAmount = (float) ($effectiveQuote?->tax ?? $latestInvoice?->tax ?? 0.00);
                    $grandTotal = $effectiveQuote ? (float) $effectiveQuote->total : ($serviceCost + $taxAmount);
                    $tipAmount = (float) ($latestInvoice?->tip_amount ?? 0.00);

                    $adminCommission = round($serviceCost * ($commissionRate / 100), 2);
                    $totalPayableToProvider = round($grandTotal - $adminCommission + $tipAmount, 2);
                @endphp

                <div class="p-4 rounded-xl bg-slate-950/80 border border-emerald-500/30 space-y-3 mt-4">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center space-x-1.5">
                            <span>Provider Net Settlement Breakdown 📈</span>
                        </span>
                        <span class="text-[11px] text-slate-400">
                            Commission Rate: <strong class="text-white">{{ number_format($commissionRate, 2) }}%</strong>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                        <div class="bg-slate-900/80 border border-slate-800/80 rounded-lg p-3 space-y-1">
                            <span class="text-slate-400 block text-[11px]">Service Cost (excl. Tax/Tip)</span>
                            <strong class="text-white text-sm block">${{ number_format($serviceCost, 2) }}</strong>
                        </div>
                        <div class="bg-slate-900/80 border border-slate-800/80 rounded-lg p-3 space-y-1">
                            <span class="text-slate-400 block text-[11px]">Grand Total (incl. Tax)</span>
                            <strong class="text-white text-sm block">${{ number_format($grandTotal, 2) }}</strong>
                        </div>
                        <div class="bg-rose-950/30 border border-rose-500/30 rounded-lg p-3 space-y-1">
                            <span class="text-rose-400 block text-[11px] font-semibold">Admin Commission ({{ number_format($commissionRate, 2) }}% × Service Cost)</span>
                            <strong class="text-rose-400 text-sm block">-${{ number_format($adminCommission, 2) }}</strong>
                        </div>
                        <div class="bg-emerald-950/40 border border-emerald-500/40 rounded-lg p-3 space-y-1">
                            <span class="text-emerald-400 block text-[11px] font-bold">Total Payable to You</span>
                            <strong class="text-emerald-400 text-sm block">${{ number_format($totalPayableToProvider, 2) }}</strong>
                            @if($tipAmount > 0)
                                <span class="text-[10px] text-indigo-300 block">(Includes +${{ number_format($tipAmount, 2) }} Tip 🎁)</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between flex-wrap gap-3">
                    <div class="text-xs text-slate-400">
                        <span class="text-emerald-400 font-semibold">Payment Completed:</span> Customer has settled the final balance. Download invoice below.
                    </div>
                    <a href="{{ route('provider.requests.invoice.download', $jobRequest->id ?? $serviceRequest->id) }}" target="_blank" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-md flex items-center space-x-2">
                        <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Download Final Invoice 📄</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- Step 3: Commercial Quotation & Provider Approval -->
    @if($quote)
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4 mt-6">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">3. Commercial Quotation</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($quote->technician)
                            Submitted by technician: <strong class="text-white">{{ $quote->technician->name }}</strong>
                        @else
                            Generated by Provider
                        @endif
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    @if(! $isCompleted)
                        <button type="button" onclick="toggleEditQuotePanel()" class="px-3 py-1.5 rounded-xl bg-amber-500/20 border border-amber-500/40 text-amber-300 hover:bg-amber-500/30 text-xs font-bold transition flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            <span>Edit Quote</span>
                        </button>
                    @else
                        <span class="px-3 py-1.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 text-xs font-semibold flex items-center space-x-1.5 cursor-not-allowed">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Quote Locked (Job Completed)</span>
                        </span>
                    @endif
                    @if($quote->is_approved_by_provider)
                        <span class="px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase">
                            Approved by Provider ✓
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold uppercase">
                            Awaiting Provider Approval ⏳
                        </span>
                    @endif
                </div>
            </div>

            <!-- Read-only Summary Items Table -->
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Price</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($quote->items as $item)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-white">{{ $item->description }}</td>
                                <td class="px-4 py-3 capitalize text-slate-400">{{ str_replace('_', ' ', $item->type) }}</td>
                                <td class="px-4 py-3 text-right">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-400">${{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pt-2 border-t border-slate-800/80 gap-4">
                <div class="text-xs space-y-1">
                    <div class="text-slate-400">Labor Subtotal: <strong class="text-white">${{ number_format($quote->labor_subtotal, 2) }}</strong></div>
                    <div class="text-slate-400">Material Subtotal: <strong class="text-white">${{ number_format($quote->material_subtotal, 2) }}</strong></div>
                    <div class="text-slate-400">Tax Amount: <strong class="text-white">${{ number_format($quote->tax, 2) }}</strong></div>
                    <div class="text-slate-400">Discount: <strong class="text-white">${{ number_format($quote->discount, 2) }}</strong></div>
                    <div class="text-slate-400">Total Quote Amount: <strong class="text-amber-400 font-bold text-sm">${{ number_format($quote->total, 2) }}</strong></div>
                </div>

                @if(! $quote->is_approved_by_provider)
                    <form action="{{ route('provider.quotes.approve', $quote->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-lg flex items-center space-x-2">
                            <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            <span>Approve & Release Quote to Customer</span>
                        </button>
                    </form>
                @endif
            </div>

            <!-- Edit Quote Form Panel -->
            @if(! $isCompleted)
                <div id="edit-quote-panel" class="{{ $errors->any() ? '' : 'hidden' }} pt-4 border-t border-slate-800/80 space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-amber-400">Edit Quotation Line Items & Pricing</h4>
                        <span class="text-xs text-slate-400">Modify line items before approving for customer</span>
                    </div>

                    <form action="{{ route('provider.quotes.update', $quote->id) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-950 p-2">
                            <table class="w-full text-left text-xs text-slate-300">
                                <thead class="bg-slate-900/80 text-slate-400 uppercase text-[10px]">
                                    <tr>
                                        <th class="px-3 py-2">Description</th>
                                        <th class="px-3 py-2 w-36">Type</th>
                                        <th class="px-3 py-2 w-24 text-right">Qty</th>
                                        <th class="px-3 py-2 w-28 text-right">Unit Price ($)</th>
                                        <th class="px-3 py-2 w-24 text-right">Total</th>
                                        <th class="px-3 py-2 w-10 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="quote-items-body" class="divide-y divide-slate-800/60">
                                    @foreach($quote->items as $index => $item)
                                        <tr class="quote-item-row">
                                            <td class="px-3 py-2">
                                                <input type="text" name="items[{{ $index }}][description]" value="{{ old("items.{$index}.description", $item->description) }}" required class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-400">
                                            </td>
                                            <td class="px-3 py-2">
                                                <select name="items[{{ $index }}][type]" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-amber-400">
                                                    <option value="labor" {{ old("items.{$index}.type", $item->type) === 'labor' ? 'selected' : '' }}>Labor</option>
                                                    <option value="material" {{ old("items.{$index}.type", $item->type) === 'material' ? 'selected' : '' }}>Material</option>
                                                    <option value="additional_service" {{ old("items.{$index}.type", $item->type) === 'additional_service' ? 'selected' : '' }}>Additional Service</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ old("items.{$index}.quantity", $item->quantity) }}" step="0.01" min="0.01" required onchange="calculateTotals()" oninput="calculateTotals()" class="w-20 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-right text-white focus:outline-none focus:border-amber-400 item-qty">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number" name="items[{{ $index }}][unit_price]" value="{{ old("items.{$index}.unit_price", $item->unit_price) }}" step="0.01" min="0" required onchange="calculateTotals()" oninput="calculateTotals()" class="w-24 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-right text-white focus:outline-none focus:border-amber-400 item-price">
                                            </td>
                                            <td class="px-3 py-2 text-right font-semibold text-amber-400 item-total">
                                                ${{ number_format($item->total_price, 2) }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" onclick="removeQuoteItem(this)" class="text-rose-400 hover:text-rose-300 text-base font-bold">&times;</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="mt-3 px-2">
                                <button type="button" onclick="addQuoteItem()" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-semibold border border-slate-700 transition flex items-center space-x-1">
                                    <span>+ Add Item Line</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                            <div>
                                <label class="block text-xs text-slate-400 mb-1 font-semibold">Tax Rate (e.g. 0.05 for 5%)</label>
                                <input type="number" name="tax_rate" id="tax-rate-input" value="{{ old('tax_rate', 0.05) }}" step="0.01" min="0" max="1" onchange="calculateTotals()" oninput="calculateTotals()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1 font-semibold">Discount Amount ($)</label>
                                <input type="number" name="discount" id="discount-input" value="{{ old('discount', $quote->discount ?? 0.00) }}" step="0.01" min="0" onchange="calculateTotals()" oninput="calculateTotals()" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-400 mb-1 font-semibold">Calculated Total</label>
                                <div class="bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-amber-400 h-9 flex items-center" id="calc-total">
                                    ${{ number_format($quote->total, 2) }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs text-slate-400 mb-1 font-semibold">Notes / Special Instructions</label>
                            <textarea name="notes" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-amber-400" placeholder="Optional notes for customer...">{{ old('notes', $quote->notes) }}</textarea>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-2">
                            <button type="button" onclick="toggleEditQuotePanel()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition">
                                Cancel
                            </button>
                            <button type="submit" name="approve" value="0" class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs transition">
                                Save Edits (Draft)
                            </button>
                            <button type="submit" name="approve" value="1" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-lg">
                                Save & Approve Quote
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        <script>
            function toggleEditQuotePanel() {
                const el = document.getElementById('edit-quote-panel');
                if (el) {
                    el.classList.toggle('hidden');
                }
            }

            function addQuoteItem() {
                const tableBody = document.getElementById('quote-items-body');
                const index = tableBody.children.length;
                const row = document.createElement('tr');
                row.className = 'quote-item-row';
                row.innerHTML = `
                    <td class="px-3 py-2">
                        <input type="text" name="items[${index}][description]" required placeholder="Item description" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-xs text-white focus:outline-none focus:border-amber-400">
                    </td>
                    <td class="px-3 py-2">
                        <select name="items[${index}][type]" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-white focus:outline-none focus:border-amber-400">
                            <option value="labor">Labor</option>
                            <option value="material" selected>Material</option>
                            <option value="additional_service">Additional Service</option>
                        </select>
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="items[${index}][quantity]" value="1" step="0.01" min="0.01" required onchange="calculateTotals()" oninput="calculateTotals()" class="w-20 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-right text-white focus:outline-none focus:border-amber-400 item-qty">
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" name="items[${index}][unit_price]" value="0.00" step="0.01" min="0" required onchange="calculateTotals()" oninput="calculateTotals()" class="w-24 bg-slate-900 border border-slate-800 rounded-lg px-2 py-1.5 text-xs text-right text-white focus:outline-none focus:border-amber-400 item-price">
                    </td>
                    <td class="px-3 py-2 text-right font-semibold text-amber-400 item-total">$0.00</td>
                    <td class="px-3 py-2 text-center">
                        <button type="button" onclick="removeQuoteItem(this)" class="text-rose-400 hover:text-rose-300 text-base font-bold">&times;</button>
                    </td>
                `;
                tableBody.appendChild(row);
                calculateTotals();
            }

            function removeQuoteItem(btn) {
                const tableBody = document.getElementById('quote-items-body');
                if (tableBody.children.length > 1) {
                    btn.closest('tr').remove();
                    reindexRows();
                    calculateTotals();
                } else {
                    alert('Quotation must contain at least one item.');
                }
            }

            function reindexRows() {
                const rows = document.querySelectorAll('.quote-item-row');
                rows.forEach((row, i) => {
                    const desc = row.querySelector('[name*="[description]"]');
                    if (desc) desc.name = `items[${i}][description]`;
                    const type = row.querySelector('[name*="[type]"]');
                    if (type) type.name = `items[${i}][type]`;
                    const qty = row.querySelector('[name*="[quantity]"]');
                    if (qty) qty.name = `items[${i}][quantity]`;
                    const price = row.querySelector('[name*="[unit_price]"]');
                    if (price) price.name = `items[${i}][unit_price]`;
                });
            }

            function calculateTotals() {
                const rows = document.querySelectorAll('.quote-item-row');
                let grandSubtotal = 0;
                rows.forEach(row => {
                    const qty = parseFloat(row.querySelector('.item-qty')?.value || 0);
                    const price = parseFloat(row.querySelector('.item-price')?.value || 0);
                    const total = (qty * price);
                    grandSubtotal += total;
                    const totalCell = row.querySelector('.item-total');
                    if (totalCell) {
                        totalCell.textContent = '$' + total.toFixed(2);
                    }
                });
                const taxRate = parseFloat(document.getElementById('tax-rate-input')?.value || 0.05);
                const discount = parseFloat(document.getElementById('discount-input')?.value || 0.00);
                const tax = grandSubtotal * taxRate;
                const total = Math.max(0, grandSubtotal + tax - discount);
                if (document.getElementById('calc-total')) {
                    document.getElementById('calc-total').textContent = '$' + total.toFixed(2);
                }
            }
        </script>
    @endif
</div>
@endsection
