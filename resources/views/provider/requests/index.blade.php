@extends('layouts.provider')

@section('title', $tab === 'accepted' ? 'Accepted Jobs' : 'Incoming Requests')

@section('content')
<div class="space-y-6">
    <!-- Header & Top Navigation Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">
                {{ $tab === 'accepted' ? 'Accepted & Active Jobs' : 'Incoming Job Requests' }}
            </h2>
            <p class="text-xs text-slate-400 mt-1">
                {{ $tab === 'accepted' ? 'Manage accepted jobs, active contracts, and technician dispatches.' : 'Review new job invitations dispatched to your company and accept requests.' }}
            </p>
        </div>

        <!-- Section Split Navigation Tabs -->
        <div class="flex items-center space-x-2 bg-slate-950/80 p-1.5 rounded-xl border border-slate-800">
            <a href="{{ route('provider.requests.incoming') }}" 
               class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'incoming' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-400 hover:text-white' }}">
                Incoming Requests
            </a>
            <a href="{{ route('provider.requests.accepted') }}" 
               class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'accepted' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-400 hover:text-white' }}">
                Accepted Jobs
            </a>
        </div>
    </div>

    <!-- Filters & Search -->
    <form method="GET" action="{{ route('provider.requests.' . $tab) }}" class="flex flex-col md:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by request number or description..." 
                   class="w-full bg-slate-900/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
        </div>
        
        @if($tab === 'accepted')
            <select name="status" class="bg-slate-900/80 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition w-full md:w-52">
                <option value="">All Accepted Statuses</option>
                <option value="selected" {{ request('status') === 'selected' ? 'selected' : '' }}>Accepted / Awarded</option>
                <option value="assigned" {{ request('status') === 'assigned' ? 'selected' : '' }}>Technician Assigned</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        @endif

        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold border border-slate-700 transition w-full md:w-auto">
            Search
        </button>
    </form>

    <!-- Requests Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Service</th>
                        <th class="px-6 py-4">Location</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Preferred Date</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($jobRequests as $req)
                        @if($req->serviceRequest)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 font-bold text-white">
                                {{ $req->serviceRequest?->request_number ?? 'SR-'.$req->service_request_id }}
                            </td>
                            <td class="px-6 py-4 text-amber-400 font-semibold">
                                {{ $req->serviceRequest?->category?->name ?? $req->serviceRequest?->service?->name ?? 'General Service' }}
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ Str::limit($req->serviceRequest?->address_line ?? 'Customer Location', 30) }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    if ($tab === 'accepted') {
                                        $srStatus = $req->serviceRequest?->status;
                                        $val = is_object($srStatus) ? $srStatus->value : $srStatus;
                                        $label = match($val) {
                                            'awarded', 'selected', 'accepted' => 'Accepted',
                                            'assigned' => 'Technician Assigned',
                                            'in_progress' => 'In Progress',
                                            'completed' => 'Completed',
                                            'cancelled' => 'Cancelled',
                                            default => ucfirst(str_replace('_', ' ', (string) $val)),
                                        };
                                    } else {
                                        $pStatus = $req->status;
                                        $val = is_object($pStatus) ? $pStatus->value : $pStatus;
                                        $label = match($val) {
                                            'sent' => 'New Request',
                                            'viewed' => 'Viewed',
                                            'declined' => 'Declined',
                                            'expired' => 'Expired',
                                            default => ucfirst(str_replace('_', ' ', (string) $val)),
                                        };
                                    }
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold 
                                    @if(in_array($val, ['selected', 'accepted', 'awarded'])) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                    @elseif($val === 'assigned') bg-blue-500/10 text-blue-400 border border-blue-500/20
                                    @elseif($val === 'in_progress') bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                                    @elseif($val === 'completed') bg-purple-500/10 text-purple-400 border border-purple-500/20
                                    @elseif(in_array($val, ['sent', 'viewed'])) bg-amber-400/10 text-amber-400 border border-amber-400/20
                                    @else bg-slate-800 text-slate-400 border border-slate-700 @endif">
                                    {{ $label }}
                                </span>
                                @php
                                    $reqQuote = $req->serviceRequest?->quotes?->sortByDesc('id')->first();
                                @endphp
                                @if($reqQuote)
                                    <div class="mt-1.5">
                                        @if(! $reqQuote->is_approved_by_provider)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-400/20 text-amber-300 border border-amber-400/40">
                                                ⏳ Quote Awaiting Approval (${{ number_format((float) $reqQuote->total, 2) }})
                                            </span>
                                        @elseif($reqQuote->status === \App\Enums\QuoteStatus::Approved || (is_string($reqQuote->status) && $reqQuote->status === 'approved'))
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                ✓ Quote Approved (${{ number_format((float) $reqQuote->total, 2) }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-500/10 text-blue-300 border border-blue-500/20">
                                                Quote Sent to Customer (${{ number_format((float) $reqQuote->total, 2) }})
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-xs">
                                @if($req->serviceRequest?->preferred_date)
                                    <div class="font-bold text-white flex items-center space-x-1.5">
                                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <span>{{ $req->serviceRequest->preferred_date->format('M d, Y') }}</span>
                                    </div>
                                    @if($req->serviceRequest->preferred_start_time)
                                        <div class="text-[11px] text-amber-400/90 font-medium mt-1 pl-5">
                                            {{ \Carbon\Carbon::parse($req->serviceRequest->preferred_start_time)->format('g:i A') }}
                                            @if($req->serviceRequest->preferred_end_time)
                                                - {{ \Carbon\Carbon::parse($req->serviceRequest->preferred_end_time)->format('g:i A') }}
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic">Flexible / ASAP</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('provider.requests.show', $req->id) }}" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg border border-amber-400/30 text-amber-400 hover:bg-amber-400/10 transition">
                                    {{ $tab === 'incoming' ? 'View & Accept →' : 'View Details →' }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                {{ $tab === 'incoming' ? 'No new incoming job requests found.' : 'No accepted jobs found.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($jobRequests->hasPages())
            <div class="px-6 py-4 border-t border-slate-800/80">
                {{ $jobRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
