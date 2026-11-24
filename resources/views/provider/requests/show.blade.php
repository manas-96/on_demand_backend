@extends('layouts.provider')

@section('title', 'Job Request Details')

@section('content')
<!-- Firebase JS SDK (compat) -->
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore-compat.js"></script>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Request {{ $serviceRequest->request_number }}</h2>
            <p class="text-xs text-slate-400 mt-1">Detailed view of service request and technician assignment state.</p>
        </div>
        <div class="flex items-center space-x-3">
            @php
                $jobReqStatus = is_object($jobRequest->status) ? $jobRequest->status->value : $jobRequest->status;
                $statusVal = is_object($serviceRequest->status) ? $serviceRequest->status->value : $serviceRequest->status;
                $assignmentStatus = $assignment && is_object($assignment->status) ? $assignment->status->value : ($assignment->status ?? '');
                $isAccepted = in_array($jobReqStatus, ['selected', 'accepted']) || in_array($statusVal, ['awarded', 'assigned', 'in_progress', 'completed']) || ($assignment !== null);
                $isCompleted = ($statusVal === 'completed' || $assignmentStatus === 'completed');
                $latestInvoice = $serviceRequest->invoices()->latest()->first();
                $isPaid = ($latestInvoice && $latestInvoice->status === \App\Enums\InvoiceStatus::Paid) || ($serviceRequest->invoices()->where('status', \App\Enums\InvoiceStatus::Paid)->exists());
            @endphp
            @if($isAccepted)
                <button type="button" onclick="toggleChatModal()" class="relative px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-sm font-bold transition flex items-center space-x-2 shadow-sm">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span>Customer Chat 💬</span>
                    <span id="chat-unread-badge" class="hidden absolute -top-2 -right-2 min-w-[20px] h-5 px-1.5 rounded-full bg-rose-500 text-white text-[11px] font-extrabold flex items-center justify-center shadow-lg border border-slate-900 animate-pulse">0</span>
                </button>
            @endif
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
                    <span class="text-slate-500 block mb-1">Preferred Time</span>
                    <span class="text-slate-200 font-semibold">
                        @if($serviceRequest->preferred_start_time)
                            {{ \Carbon\Carbon::parse($serviceRequest->preferred_start_time)->format('g:i A') }}
                            @if($serviceRequest->preferred_end_time)
                                - {{ \Carbon\Carbon::parse($serviceRequest->preferred_end_time)->format('g:i A') }}
                            @endif
                        @else
                            Flexible
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Location & Customer Info -->
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Location & Contact</h3>
            <div>
                <div class="text-xs text-slate-500 mb-1">Service Address</div>
                <div class="text-sm text-slate-200 font-medium">{{ $serviceRequest->address_line }}</div>
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

    <!-- Pre-Work Site Inspection Photos & Findings -->
    @if($inspections && $inspections->isNotEmpty())
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                    </span>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Pre-Work Inspection Photos & Findings</h3>
                </div>
                <span class="text-xs text-slate-500 font-medium">{{ $inspections->count() }} {{ \Illuminate\Support\Str::plural('report', $inspections->count()) }}</span>
            </div>

            @foreach($inspections as $inspection)
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="text-slate-400">Inspected by:</span>
                            <span class="text-amber-400 font-semibold">{{ $inspection->technician?->name ?? 'Assigned Technician' }}</span>
                        </div>
                        <span class="text-slate-500">{{ $inspection->created_at?->inDisplayTimezone()->format('M d, Y • h:i A') }}</span>
                    </div>

                    @if($inspection->technical_notes)
                        <div class="text-xs text-slate-300 bg-slate-900/50 p-3 rounded-lg border border-slate-800/60">
                            <span class="text-slate-400 font-semibold block mb-1">Technician Notes:</span>
                            {{ $inspection->technical_notes }}
                        </div>
                    @endif

                    @if($inspection->estimated_labor_hours || $inspection->estimated_material_cost)
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            @if($inspection->estimated_labor_hours)
                                <div class="bg-slate-900/40 p-2.5 rounded-lg border border-slate-800/50">
                                    <span class="text-slate-500 block mb-0.5">Est. Labor Hours</span>
                                    <span class="text-slate-200 font-bold">{{ $inspection->estimated_labor_hours }} hrs</span>
                                </div>
                            @endif
                            @if($inspection->estimated_material_cost)
                                <div class="bg-slate-900/40 p-2.5 rounded-lg border border-slate-800/50">
                                    <span class="text-slate-500 block mb-0.5">Est. Materials Cost</span>
                                    <span class="text-emerald-400 font-bold">${{ number_format((float) $inspection->estimated_material_cost, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    @php
                        $inspPhotos = is_array($inspection->photos) ? $inspection->photos : [];
                    @endphp

                    @if(count($inspPhotos))
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">Inspection Photos ({{ count($inspPhotos) }})</div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach($inspPhotos as $photoPath)
                                    @php
                                        $cleanPath = ltrim($photoPath, '/');
                                        if (str_starts_with($cleanPath, 'storage/')) {
                                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'storage/');
                                        } elseif (str_starts_with($cleanPath, 'public/')) {
                                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'public/');
                                        }
                                        $imgSrc = str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')
                                            ? $photoPath
                                            : asset('storage/' . $cleanPath);
                                    @endphp
                                    <div class="group relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 aspect-video flex items-center justify-center">
                                        <img src="{{ $imgSrc }}" alt="Pre-work Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'text-xs text-slate-500 p-2 text-center\'>Photo Unavailable</span>';">
                                        <a href="{{ $imgSrc }}" target="_blank" class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold">
                                            View Photo ↗
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-500 italic">No photos attached to this inspection report.</p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Post-Work Proof Photos & Completion Status -->
    @if($progressUpdates && $progressUpdates->isNotEmpty())
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                <div class="flex items-center space-x-2">
                    <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </span>
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Post-Work Proof Photos & Progress Updates</h3>
                </div>
                <span class="text-xs text-slate-500 font-medium">{{ $progressUpdates->count() }} {{ \Illuminate\Support\Str::plural('update', $progressUpdates->count()) }}</span>
            </div>

            @foreach($progressUpdates as $update)
                <div class="bg-slate-950/60 border border-slate-800 rounded-xl p-4 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div class="flex items-center space-x-2">
                            @if($update->progress_percentage >= 100)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center space-x-1">
                                    <span>✓</span>
                                    <span>100% Completed</span>
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-500/10 text-sky-400 border border-sky-500/20">
                                    {{ $update->progress_percentage }}% Progress
                                </span>
                            @endif
                            <span class="text-slate-400">by</span>
                            <span class="text-slate-200 font-semibold">{{ $update->technician?->name ?? 'Assigned Technician' }}</span>
                        </div>
                        <span class="text-slate-500">{{ $update->created_at?->inDisplayTimezone()->format('M d, Y • h:i A') }}</span>
                    </div>

                    @if($update->description)
                        <div class="text-xs text-slate-300 bg-slate-900/50 p-3 rounded-lg border border-slate-800/60">
                            {{ $update->description }}
                        </div>
                    @endif

                    @php
                        $progPhotos = is_array($update->photos) ? $update->photos : [];
                    @endphp

                    @if(count($progPhotos))
                        <div>
                            <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2">
                                {{ $update->progress_percentage >= 100 ? 'Job Completion Proof Photos' : 'Progress Photos' }} ({{ count($progPhotos) }})
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                @foreach($progPhotos as $photoPath)
                                    @php
                                        $cleanPath = ltrim($photoPath, '/');
                                        if (str_starts_with($cleanPath, 'storage/')) {
                                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'storage/');
                                        } elseif (str_starts_with($cleanPath, 'public/')) {
                                            $cleanPath = \Illuminate\Support\Str::after($cleanPath, 'public/');
                                        }
                                        $imgSrc = str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')
                                            ? $photoPath
                                            : asset('storage/' . $cleanPath);
                                    @endphp
                                    <div class="group relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 aspect-video flex items-center justify-center">
                                        <img src="{{ $imgSrc }}" alt="Post-work Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" onerror="this.onerror=null;this.parentElement.innerHTML='<span class=\'text-xs text-slate-500 p-2 text-center\'>Photo Unavailable</span>';">
                                        <a href="{{ $imgSrc }}" target="_blank" class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold">
                                            View Photo ↗
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- Placeholder when technician is assigned but photos are pending -->
    @if($assignment && $inspections->isEmpty() && $progressUpdates->isEmpty())
        <div class="bg-slate-900/40 border border-slate-800/60 rounded-2xl p-5 flex items-center space-x-3 text-xs text-slate-400">
            <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <div>
                <span class="font-semibold text-slate-300">No Technician Photos Yet:</span> Pre-work site inspection and post-work proof photos uploaded by the technician from the mobile app will automatically appear here.
            </div>
        </div>
    @endif

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

            @php
                $isAssignmentLocked = ! $serviceRequest->isTechnicianAssignmentAllowed();
            @endphp

            @if($isAssignmentLocked)
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 space-y-2">
                    <div class="flex items-center space-x-2 text-xs font-bold text-amber-400 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Assignment Locked Until Scheduled Date</span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        This booking is scheduled for <strong>{{ $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('l, F j, Y') : 'a future date' }}</strong>. 
                        Technician assignment will unlock on the day of the job.
                    </p>
                    @if($serviceRequest->getAssignmentUnlockLabel())
                        <div class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-slate-950 border border-amber-500/30 text-[11px] font-mono text-amber-400">
                            <span>⏳</span>
                            <span>{{ $serviceRequest->getAssignmentUnlockLabel() }}</span>
                        </div>
                    @endif
                </div>
            @elseif($technicians->isEmpty())
                <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider">No Active Technicians Found</div>
                    <p class="text-xs text-slate-300">You need at least one active, approved technician under your company to dispatch jobs.</p>
                    <a href="{{ route('provider.technicians.create') }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 text-xs font-bold hover:bg-amber-300 transition">
                        <span>+ Add Technician</span>
                    </a>
                </div>
            @else
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
            @endif
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

            <!-- Google Map Canvas -->
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

        @php
            $techProfile = $assignment->technician?->technicianProfile;
            $initialTechLat = ($techProfile && $techProfile->current_latitude !== null) ? (float) $techProfile->current_latitude : null;
            $initialTechLng = ($techProfile && $techProfile->current_longitude !== null) ? (float) $techProfile->current_longitude : null;
        @endphp

        <!-- Google Maps JS API -->
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.map_key', env('GOOGLE_MAPS_API_KEY', 'AIzaSyCbb_HYYcDgCFtwhzn98boM5Fw_0W84Ze4')) }}&callback=initTechLiveMap&libraries=geometry" async defer></script>

        <script>
            let techMap, techMarker, destMarker, polyline;
            let directionsService, directionsRenderer;
            let googleMapsLoaded = false;
            let pendingTechLocation = null;
            let lastRoutedOrigin = null;
            let isFetchingDirections = false;

            function calcDistanceKm(lat1, lon1, lat2, lon2) {
                if (window.google && google.maps && google.maps.geometry && google.maps.geometry.spherical) {
                    const p1 = new google.maps.LatLng(lat1, lon1);
                    const p2 = new google.maps.LatLng(lat2, lon2);
                    const meters = google.maps.geometry.spherical.computeDistanceBetween(p1, p2);
                    return meters / 1000;
                }
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon/2) * Math.sin(dLon/2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                return R * c;
            }

            function fetchDirectionsRoute(originLatLng, destLatLng) {
                if (!directionsService || !directionsRenderer) return;

                // Avoid unnecessary API queries if technician has moved less than 25 meters
                if (lastRoutedOrigin) {
                    const movedKm = calcDistanceKm(lastRoutedOrigin.lat, lastRoutedOrigin.lng, originLatLng.lat, originLatLng.lng);
                    if (movedKm < 0.025) {
                        return;
                    }
                }

                if (isFetchingDirections) return;
                isFetchingDirections = true;

                directionsService.route({
                    origin: originLatLng,
                    destination: destLatLng,
                    travelMode: google.maps.TravelMode.DRIVING,
                }, function(result, status) {
                    isFetchingDirections = false;
                    if (status === google.maps.DirectionsStatus.OK && result) {
                        lastRoutedOrigin = originLatLng;
                        directionsRenderer.setDirections(result);

                        // Hide straight polyline fallback once driving route is active
                        if (polyline) polyline.setMap(null);

                        // Extract actual driving distance & duration with traffic
                        const route = result.routes[0];
                        if (route && route.legs && route.legs.length > 0) {
                            const leg = route.legs[0];
                            const distText = leg.distance ? leg.distance.text : '';
                            const durationText = leg.duration ? leg.duration.text : '';

                            const etaEl = document.getElementById('tech-distance-eta');
                            if (etaEl && distText) {
                                etaEl.textContent = `${distText} (~${durationText} away)`;
                            }
                        }
                    } else {
                        console.warn('Google DirectionsService notice (' + status + '), falling back to geodesic path.');
                        // Fallback straight polyline if route unavailable or quota limited
                        if (polyline) {
                            polyline.setMap(techMap);
                            polyline.setPath([originLatLng, destLatLng]);
                        }
                    }
                });
            }

            function initTechLiveMap() {
                googleMapsLoaded = true;
                const mapContainer = document.getElementById('tech-live-map');
                if (!mapContainer) return;

                const destLat = parseFloat("{{ $serviceRequest->latitude ?? 12.9800 }}") || 12.9800;
                const destLng = parseFloat("{{ $serviceRequest->longitude ?? 77.6000 }}") || 77.6000;
                const techName = "{{ $assignment->technician->name }}";
                const destAddress = "{{ addslashes($serviceRequest->address_line) }}";

                const dbTechLat = {{ $initialTechLat !== null ? $initialTechLat : 'null' }};
                const dbTechLng = {{ $initialTechLng !== null ? $initialTechLng : 'null' }};

                const defaultTechLat = pendingTechLocation ? pendingTechLocation.lat : (dbTechLat !== null ? dbTechLat : destLat - 0.012);
                const defaultTechLng = pendingTechLocation ? pendingTechLocation.lng : (dbTechLng !== null ? dbTechLng : destLng - 0.009);
                const lastUpdatedStr = pendingTechLocation ? pendingTechLocation.timeStr : 'Connecting...';

                const darkMapStyle = [
                    { elementType: "geometry", stylers: [{ color: "#1e293b" }] },
                    { elementType: "labels.text.stroke", stylers: [{ color: "#0f172a" }] },
                    { elementType: "labels.text.fill", stylers: [{ color: "#94a3b8" }] },
                    { featureType: "road", elementType: "geometry", stylers: [{ color: "#334155" }] },
                    { featureType: "road", elementType: "geometry.stroke", stylers: [{ color: "#1e293b" }] },
                    { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] }
                ];

                const techLatLng = { lat: defaultTechLat, lng: defaultTechLng };
                const destLatLng = { lat: destLat, lng: destLng };

                techMap = new google.maps.Map(mapContainer, {
                    center: techLatLng,
                    zoom: 13,
                    styles: darkMapStyle,
                    disableDefaultUI: false
                });

                // Google Directions Renderer for turn-by-turn road polyline
                directionsService = new google.maps.DirectionsService();
                directionsRenderer = new google.maps.DirectionsRenderer({
                    map: techMap,
                    suppressMarkers: true,
                    preserveViewport: true,
                    polylineOptions: {
                        strokeColor: '#f59e0b',
                        strokeOpacity: 0.9,
                        strokeWeight: 5
                    }
                });

                const techIconSvg = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16" fill="#2563eb" stroke="#ffffff" stroke-width="3"/>
                        <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-size="16">🚚</text>
                    </svg>
                `);

                const destIconSvg = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 34 34">
                        <circle cx="17" cy="17" r="15" fill="#dc2626" stroke="#ffffff" stroke-width="3"/>
                        <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" font-size="15">📍</text>
                    </svg>
                `);

                techMarker = new google.maps.Marker({
                    position: techLatLng,
                    map: techMap,
                    title: `👨‍🔧 ${techName} - Live Technician Location`,
                    icon: {
                        url: techIconSvg,
                        scaledSize: new google.maps.Size(36, 36)
                    }
                });

                destMarker = new google.maps.Marker({
                    position: destLatLng,
                    map: techMap,
                    title: `📍 Service Location: ${destAddress}`,
                    icon: {
                        url: destIconSvg,
                        scaledSize: new google.maps.Size(34, 34)
                    }
                });

                const techInfoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 4px 6px; font-family: ui-sans-serif, system-ui, sans-serif;">
                            <div style="font-weight: 700; font-size: 12px; color: #2563eb; display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                                <span>👨‍🔧</span> Assigned Technician
                            </div>
                            <div style="font-weight: 700; font-size: 14px; color: #0f172a;">${techName}</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">Live Location Tracker</div>
                        </div>
                    `
                });

                const destInfoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 4px 6px; font-family: ui-sans-serif, system-ui, sans-serif;">
                            <div style="font-weight: 700; font-size: 12px; color: #dc2626; display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                                <span>📍</span> Service Destination
                            </div>
                            <div style="font-weight: 600; font-size: 12px; color: #1e293b; max-width: 220px; line-height: 1.3;">${destAddress}</div>
                        </div>
                    `
                });

                techMarker.addListener('click', function () {
                    destInfoWindow.close();
                    techInfoWindow.open(techMap, techMarker);
                });

                destMarker.addListener('click', function () {
                    techInfoWindow.close();
                    destInfoWindow.open(techMap, destMarker);
                });

                // Standby straight polyline for initial display and fallback
                polyline = new google.maps.Polyline({
                    path: [techLatLng, destLatLng],
                    geodesic: true,
                    strokeColor: '#f59e0b',
                    strokeOpacity: 0.6,
                    strokeWeight: 4,
                    map: techMap
                });

                const bounds = new google.maps.LatLngBounds();
                bounds.extend(techLatLng);
                bounds.extend(destLatLng);
                techMap.fitBounds(bounds, { top: 40, right: 40, bottom: 40, left: 40 });

                updateLocationDetails(defaultTechLat, defaultTechLng, destLat, destLng, lastUpdatedStr);

                // Fetch Google Directions road navigation route
                fetchDirectionsRoute(techLatLng, destLatLng);
            }

            function updateLocationDetails(techLat, techLng, destLat, destLng, lastUpdatedStr) {
                const distKm = calcDistanceKm(techLat, techLng, destLat, destLng);
                let distText = '';

                if (distKm < 0.15) {
                    distText = 'Arrived at Location';
                } else {
                    const etaMins = Math.max(1, Math.round((distKm / 25) * 60));
                    distText = `${distKm.toFixed(1)} km (~${etaMins} mins away)`;
                }

                const etaEl = document.getElementById('tech-distance-eta');
                if (etaEl && (etaEl.textContent === 'Calculating distance...' || !lastRoutedOrigin)) {
                    etaEl.textContent = distText;
                }

                const pingEl = document.getElementById('tech-last-ping');
                if (pingEl) pingEl.textContent = `Live Status: ${lastUpdatedStr}`;
            }

            function updateTechPositionOnMap(techLat, techLng, lastUpdatedStr) {
                const destLat = parseFloat("{{ $serviceRequest->latitude ?? 12.9800 }}") || 12.9800;
                const destLng = parseFloat("{{ $serviceRequest->longitude ?? 77.6000 }}") || 77.6000;

                pendingTechLocation = { lat: techLat, lng: techLng, timeStr: lastUpdatedStr };

                if (!googleMapsLoaded || !techMap) return;

                const techLatLng = { lat: techLat, lng: techLng };
                const destLatLng = { lat: destLat, lng: destLng };

                if (techMarker) techMarker.setPosition(techLatLng);
                if (destMarker) destMarker.setPosition(destLatLng);

                const pingEl = document.getElementById('tech-last-ping');
                if (pingEl) pingEl.textContent = `Live Status: ${lastUpdatedStr}`;

                // Recalculate road routing along actual streets
                fetchDirectionsRoute(techLatLng, destLatLng);
            }

            document.addEventListener('DOMContentLoaded', function () {
                const techUserId = "{{ $assignment->technician_id }}";

                function fetchBackendTechnicianLocation() {
                    fetch("{{ route('provider.requests.technician-location', $serviceRequest->id) }}")
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.latitude && data.longitude) {
                                updateTechPositionOnMap(parseFloat(data.latitude), parseFloat(data.longitude), data.last_updated || 'Synced');
                            }
                        })
                        .catch(err => console.warn('Backend location sync notice:', err));
                }

                fetchBackendTechnicianLocation();

                const firebaseConfig = {
                    apiKey: "{{ config('services.firebase.api_key') }}",
                    authDomain: "{{ config('services.firebase.project_id') }}.firebaseapp.com",
                    projectId: "{{ config('services.firebase.project_id') }}",
                    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
                    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
                    appId: "{{ config('services.firebase.app_id') }}"
                };

                if (!window.firebase || !firebase.apps.length) {
                    if (window.firebase) firebase.initializeApp(firebaseConfig);
                }
                if (window.firebase) {
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
                                updateTechPositionOnMap(lat, lng, formattedTime);
                                return true;
                            }
                        }
                        return false;
                    }

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
                }
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
    @else
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 backdrop-blur-xl space-y-3 mt-6">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">3. Commercial Quotation</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Technician on-site inspection & quotation</p>
                </div>
                <span class="px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-400 text-xs font-medium">
                    No Quote Submitted Yet ⏳
                </span>
            </div>
            <p class="text-xs text-slate-400">
                The assigned technician has not submitted a commercial quotation yet. Once submitted from the technician mobile app, it will immediately appear here for your review and approval.
            </p>
        </div>
    @endif
</div>

    @if($isAccepted)
        <!-- Customer Chat Modal -->
        <div id="customer-chat-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4 transition-all duration-200">
            <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl overflow-hidden flex flex-col h-[560px] max-h-[90vh]">
                <!-- Chat Modal Header -->
                <div class="p-4 bg-slate-950 border-b border-slate-800 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-base shadow-inner">
                            👤
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white tracking-tight flex items-center space-x-2">
                                <span>{{ $serviceRequest->customer->name ?? 'Customer' }}</span>
                                <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">Customer</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">Job Ref: <span class="text-amber-400 font-mono font-semibold">{{ $serviceRequest->request_number }}</span></p>
                        </div>
                    </div>
                    <button onclick="toggleChatModal()" type="button" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-xl transition" title="Close chat (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Chat Messages Scroll Area Wrapper with Floating Jump Button -->
                <style>
                    #chat-messages-list::-webkit-scrollbar {
                        width: 6px;
                    }
                    #chat-messages-list::-webkit-scrollbar-track {
                        background: rgba(15, 23, 42, 0.6);
                        border-radius: 8px;
                    }
                    #chat-messages-list::-webkit-scrollbar-thumb {
                        background: rgba(100, 116, 139, 0.6);
                        border-radius: 8px;
                    }
                    #chat-messages-list::-webkit-scrollbar-thumb:hover {
                        background: rgba(245, 158, 11, 0.8);
                    }
                </style>
                <div class="relative flex-1 min-h-0 overflow-hidden flex flex-col bg-slate-950/50">
                    <div id="chat-messages-list" class="flex-1 min-h-0 p-4 overflow-y-auto overscroll-contain space-y-3">
                        <div id="chat-loading" class="text-center text-xs text-slate-500 py-12">
                            Loading real-time chat messages...
                        </div>
                    </div>

                    <!-- Jump to Latest Floating Pill Button -->
                    <button id="chat-scroll-bottom-btn" onclick="jumpToChatBottom(event)" type="button" class="hidden absolute bottom-3 right-4 px-3.5 py-1.5 rounded-full bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold shadow-xl flex items-center space-x-1.5 transition-all duration-200 z-30 cursor-pointer select-none animate-bounce">
                        <span>Latest messages</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
                    </button>
                </div>

                <!-- Chat Input Footer -->
                <form id="chat-send-form" class="p-3 bg-slate-950 border-t border-slate-800 flex items-center space-x-2">
                    <input type="text" id="chat-message-input" autocomplete="off" placeholder="Type your message to customer..." class="flex-1 bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400">
                    <button type="submit" id="chat-submit-btn" class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-sm transition flex items-center space-x-1.5 shadow-md">
                        <span>Send</span>
                        <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <script>
            let isChatModalOpen = false;
            let userScrolledUp = false;
            let isAutoScrolling = false;
            window.chatMessagesDocs = [];

            const providerId = {{ (int) auth()->id() }};
            const customerId = {{ (int) $serviceRequest->customer_id }};
            const customerName = "{{ addslashes($serviceRequest->customer->name ?? 'Customer') }}";
            const technicianId = {{ (int) ($assignment?->technician_id ?? 0) }};
            const technicianName = "{{ addslashes($assignment?->technician?->name ?? 'Technician') }}";

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function parseMsgTime(d) {
                if (d.created_at) {
                    if (typeof d.created_at.toDate === 'function') {
                        return d.created_at.toDate().getTime();
                    }
                    if (d.created_at.seconds) {
                        return d.created_at.seconds * 1000;
                    }
                    if (typeof d.created_at === 'string') {
                        const p = Date.parse(d.created_at);
                        if (!isNaN(p)) return p;
                    }
                }
                if (d.delivered_at) {
                    const p = Date.parse(d.delivered_at);
                    if (!isNaN(p)) return p;
                }
                return 0;
            }

            function formatMsgTime(d) {
                if (d.created_at && typeof d.created_at.toDate === 'function') {
                    return d.created_at.toDate().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                if (d.created_at && d.created_at.seconds) {
                    return new Date(d.created_at.seconds * 1000).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                if (d.created_at && typeof d.created_at === 'string') {
                    const p = Date.parse(d.created_at);
                    if (!isNaN(p)) return new Date(p).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
                if (d.delivered_at) {
                    try {
                        const p = Date.parse(d.delivered_at);
                        if (!isNaN(p)) return new Date(p).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    } catch(e) {}
                }
                return '';
            }

            function updateScrollState() {
                if (isAutoScrolling) return;
                const container = document.getElementById('chat-messages-list');
                const jumpBtn = document.getElementById('chat-scroll-bottom-btn');
                if (!container) return;

                const distFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;
                userScrolledUp = (distFromBottom > 45);

                if (jumpBtn) {
                    if (userScrolledUp) {
                        jumpBtn.classList.remove('hidden');
                    } else {
                        jumpBtn.classList.add('hidden');
                    }
                }
            }

            function toggleChatModal() {
                const modal = document.getElementById('customer-chat-modal');
                if (!modal) return;
                isChatModalOpen = !isChatModalOpen;
                if (isChatModalOpen) {
                    modal.classList.remove('hidden');
                    userScrolledUp = false;
                    markMessagesAsSeen();

                    // Multi-frame scroll to bottom to ensure layout has settled
                    requestAnimationFrame(() => {
                        scrollChatToBottom(false);
                        setTimeout(() => scrollChatToBottom(false), 50);
                        setTimeout(() => scrollChatToBottom(false), 150);
                    });

                    const input = document.getElementById('chat-message-input');
                    if (input) setTimeout(() => input.focus(), 100);
                } else {
                    modal.classList.add('hidden');
                }
            }

            function scrollChatToBottom(smooth = false) {
                const container = document.getElementById('chat-messages-list');
                if (!container) return;

                if (smooth && typeof container.scrollTo === 'function') {
                    container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                } else {
                    container.scrollTop = container.scrollHeight;
                }

                const jumpBtn = document.getElementById('chat-scroll-bottom-btn');
                if (jumpBtn) jumpBtn.classList.add('hidden');
            }
            window.jumpToChatBottom = function(e) {
                if (e && typeof e.preventDefault === 'function') {
                    e.preventDefault();
                    e.stopPropagation();
                }
                const container = document.getElementById('chat-messages-list');
                const jumpBtn = document.getElementById('chat-scroll-bottom-btn');
                if (!container) return;

                userScrolledUp = false;
                isAutoScrolling = true;
                if (jumpBtn) jumpBtn.classList.add('hidden');

                try {
                    container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
                } catch (err) {
                    container.scrollTop = container.scrollHeight;
                }

                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                    isAutoScrolling = false;
                    userScrolledUp = false;
                    if (jumpBtn) jumpBtn.classList.add('hidden');
                }, 350);
            };

            function markMessagesAsSeen() {
                if (!window.firebase || !window.chatMessagesDocs || !window.chatMessagesDocs.length) return;

                window.chatMessagesDocs.forEach(doc => {
                    const data = doc.data();
                    const sId = String(data.sender_id ?? '').trim();
                    const isCustomer = (sId === String(customerId) || (sId !== String(providerId) && (technicianId === 0 || sId !== String(technicianId))));

                    if (isCustomer && !data.seen_at && doc.ref && typeof doc.ref.update === 'function') {
                        doc.ref.update({
                            seen_at: firebase.firestore.FieldValue.serverTimestamp()
                        }).catch(() => {
                            // Fail silently if Firestore rules restrict update without Firebase Auth
                        });
                    }
                });
            }

            function initScrollTracking() {
                const container = document.getElementById('chat-messages-list');
                const jumpBtn = document.getElementById('chat-scroll-bottom-btn');

                if (jumpBtn) {
                    jumpBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        window.jumpToChatBottom(e);
                    });
                }

                if (container) {
                    container.addEventListener('scroll', updateScrollState, { passive: true });
                    container.addEventListener('wheel', () => updateScrollState(), { passive: true });
                    container.addEventListener('touchmove', () => updateScrollState(), { passive: true });
                }
            }

            function renderMessageHtml(data) {
                const sId = String(data.sender_id ?? '').trim();
                const rId = String(data.receiver_id ?? '').trim();
                const cId = String(customerId);
                const pId = String(providerId);
                const tId = String(technicianId);

                // Smart ownership: Customer vs Provider Team
                const isFromCustomer = (sId === cId || (rId !== '' && (rId === pId || (tId !== '0' && rId === tId)) && sId !== pId && sId !== tId));
                const isMine = !isFromCustomer;
                const isTechnician = isMine && (sId === tId && tId !== '0');

                const timeStr = formatMsgTime(data);

                if (isMine) {
                    const roleBadge = isTechnician
                        ? `<span class="text-sky-400 font-semibold">Technician (${escapeHtml(technicianName)})</span>`
                        : `<span class="text-amber-400 font-semibold">You (Provider)</span>`;
                    const statusStr = data.optimistic ? '• Sending...' : (data.seen_at ? '• Seen' : '• Sent');

                    return `
                        <div class="w-full flex flex-col items-end space-y-1">
                            <div class="text-[10px] text-slate-400 px-1 flex items-center space-x-1.5">
                                ${roleBadge}
                            </div>
                            <div class="max-w-[82%] bg-amber-400 text-slate-950 font-medium rounded-2xl rounded-tr-none px-4 py-2.5 text-xs sm:text-sm shadow-md leading-relaxed break-words">
                                ${escapeHtml(data.message || '')}
                            </div>
                            <div class="text-[10px] text-slate-500 px-1 font-semibold flex items-center space-x-1">
                                <span>${timeStr}</span>
                                <span>${statusStr}</span>
                            </div>
                        </div>
                    `;
                } else {
                    return `
                        <div class="w-full flex flex-col items-start space-y-1">
                            <div class="text-[10px] text-slate-400 px-1 flex items-center space-x-1.5">
                                <span class="text-emerald-400 font-semibold">Customer (${escapeHtml(customerName)})</span>
                            </div>
                            <div class="max-w-[82%] bg-slate-800 text-slate-100 font-medium rounded-2xl rounded-tl-none px-4 py-2.5 text-xs sm:text-sm shadow-md border border-slate-700/80 leading-relaxed break-words">
                                ${escapeHtml(data.message || '')}
                            </div>
                            <div class="text-[10px] text-slate-500 px-1 font-semibold">
                                <span>${timeStr}</span>
                            </div>
                        </div>
                    `;
                }
            }

            function appendOptimisticMessage(text, tempId) {
                const listContainer = document.getElementById('chat-messages-list');
                if (!listContainer) return;

                const emptyNotice = listContainer.querySelector('.text-slate-500');
                if (emptyNotice && emptyNotice.textContent.includes('No messages yet')) {
                    listContainer.innerHTML = '';
                }

                const now = new Date();
                const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                const tempHtml = `
                    <div id="optimistic-${tempId}" class="w-full flex flex-col items-end space-y-1">
                        <div class="text-[10px] text-slate-400 px-1">
                            <span class="text-amber-400 font-semibold">You (Provider)</span>
                        </div>
                        <div class="max-w-[82%] bg-amber-400/90 text-slate-950 font-medium rounded-2xl rounded-tr-none px-4 py-2.5 text-xs sm:text-sm shadow-md leading-relaxed break-words">
                            ${escapeHtml(text)}
                        </div>
                        <div class="text-[10px] text-slate-500 px-1 font-semibold flex items-center space-x-1">
                            <span>${timeStr}</span>
                            <span id="optimistic-status-${tempId}">• Sending...</span>
                        </div>
                    </div>
                `;
                listContainer.insertAdjacentHTML('beforeend', tempHtml);
            }

            async function sendViaBackend(text) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 12000);

                try {
                    const response = await fetch("{{ route('provider.requests.chat-send', $jobRequest->id) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ message: text }),
                        signal: controller.signal
                    });

                    clearTimeout(timeoutId);

                    const json = await response.json().catch(() => ({}));
                    if (!response.ok || json.success === false) {
                        throw new Error(json.message || ('Server returned HTTP ' + response.status));
                    }

                    return json;
                } catch (err) {
                    clearTimeout(timeoutId);
                    if (err.name === 'AbortError') {
                        throw new Error('Message request timed out. Please check your connection.');
                    }
                    throw err;
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                initScrollTracking();

                // Close chat modal on Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && isChatModalOpen) {
                        toggleChatModal();
                    }
                });

                // Close chat modal on backdrop click
                const modal = document.getElementById('customer-chat-modal');
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            toggleChatModal();
                        }
                    });
                }

                // Auto-open chat if navigated with #chat anchor
                if (window.location.hash === '#chat') {
                    setTimeout(() => {
                        if (!isChatModalOpen) toggleChatModal();
                    }, 250);
                }

                const sendForm = document.getElementById('chat-send-form');
                const input = document.getElementById('chat-message-input');
                const submitBtn = document.getElementById('chat-submit-btn');

                if (sendForm) {
                    sendForm.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        const text = input ? input.value.trim() : '';
                        if (!text) return;

                        const tempId = Date.now();
                        if (submitBtn) submitBtn.disabled = true;
                        if (input) input.value = '';

                        appendOptimisticMessage(text, tempId);
                        userScrolledUp = false;
                        scrollChatToBottom(false);

                        try {
                            await sendViaBackend(text);
                        } catch (err) {
                            console.error('Chat delivery failed:', err);
                            const optMsg = document.getElementById(`optimistic-${tempId}`);
                            if (optMsg) {
                                const statusSpan = document.getElementById(`optimistic-status-${tempId}`);
                                if (statusSpan) {
                                    statusSpan.className = 'text-rose-400 font-bold';
                                    statusSpan.textContent = '• Failed to send';
                                }
                            }
                            if (input) input.value = text;
                            alert('Failed to send message: ' + (err.message || 'Please check your connection and try again.'));
                        } finally {
                            if (submitBtn) submitBtn.disabled = false;
                            if (input) input.focus();
                        }
                    });
                }

                const firebaseConfig = {
                    apiKey: "{{ config('services.firebase.api_key') }}",
                    authDomain: "{{ config('services.firebase.project_id') }}.firebaseapp.com",
                    projectId: "{{ config('services.firebase.project_id') }}",
                    storageBucket: "{{ config('services.firebase.storage_bucket') }}",
                    messagingSenderId: "{{ config('services.firebase.messaging_sender_id') }}",
                    appId: "{{ config('services.firebase.app_id') }}"
                };

                if (window.firebase && (!firebase.apps || !firebase.apps.length)) {
                    firebase.initializeApp(firebaseConfig);
                }

                if (window.firebase) {
                    const db = firebase.firestore();
                    const jobId = "{{ $serviceRequest->request_number }}";
                    const requestId = "{{ (string) $serviceRequest->id }}";

                    // Match all potential job ID representations to ensure full compatibility
                    const jobIdsToQuery = Array.from(new Set([jobId, requestId, 'JOB-' + requestId, Number(requestId)])).filter(Boolean);

                    db.collection('messages')
                        .where('job_id', 'in', jobIdsToQuery)
                        .onSnapshot((snapshot) => {
                            // Sort in-memory to prevent composite index requirement errors
                            const docs = snapshot.docs.slice();
                            docs.sort((a, b) => parseMsgTime(a.data()) - parseMsgTime(b.data()));

                            window.chatMessagesDocs = docs;
                            let unreadCount = 0;
                            let html = '';

                            if (docs.length === 0) {
                                html = `<div class="text-center text-xs text-slate-500 py-16">No messages yet. Send a message to start chatting with customer!</div>`;
                            } else {
                                docs.forEach(doc => {
                                    const data = doc.data();
                                    const sId = String(data.sender_id ?? '').trim();
                                    const isFromCustomer = (sId === String(customerId) || (sId !== String(providerId) && (technicianId === 0 || sId !== String(technicianId))));

                                    if (isFromCustomer && !data.seen_at) {
                                        unreadCount++;
                                    }

                                    html += renderMessageHtml(data);
                                });
                            }

                            const listContainer = document.getElementById('chat-messages-list');
                            if (listContainer) {
                                const prevScrollTop = listContainer.scrollTop;
                                const prevScrollHeight = listContainer.scrollHeight;

                                listContainer.innerHTML = html;

                                if (!userScrolledUp) {
                                    listContainer.scrollTop = listContainer.scrollHeight;
                                } else {
                                    const heightDiff = listContainer.scrollHeight - prevScrollHeight;
                                    listContainer.scrollTop = prevScrollTop + (heightDiff > 0 ? heightDiff : 0);
                                    updateScrollState();
                                }
                            }

                            const unreadBadge = document.getElementById('chat-unread-badge');
                            if (unreadBadge) {
                                if (unreadCount > 0) {
                                    unreadBadge.textContent = unreadCount;
                                    unreadBadge.classList.remove('hidden');
                                } else {
                                    unreadBadge.classList.add('hidden');
                                }
                            }

                            if (isChatModalOpen) {
                                markMessagesAsSeen();
                            }
                        }, (error) => {
                            console.error('Firestore chat subscription notice:', error);
                            const listContainer = document.getElementById('chat-messages-list');
                            if (listContainer) {
                                listContainer.innerHTML = `
                                    <div class="text-center text-xs text-rose-400 py-12 px-4 space-y-2">
                                        <div class="font-bold">⚠️ Unable to load chat messages</div>
                                        <div class="text-slate-400 text-[11px]">${escapeHtml(error.message || 'Connection error')}</div>
                                    </div>
                                `;
                            }
                        });
                }
            });
        </script>
    @endif
</div>
@endsection
