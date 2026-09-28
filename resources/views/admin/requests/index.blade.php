@extends('layouts.admin')

@section('title', 'Service Request Inspector')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Service Requests Registry</h1>
            <p class="text-xs text-slate-400">Inspect full request lifecycles, matching invitations, provider dispatches, and job execution.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <form method="GET" action="{{ route('admin.requests.index') }}" class="space-y-4">
            <!-- Row 1: Search & Date Inputs -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                <!-- Search -->
                <div class="md:col-span-4">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Search Keyword</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search request # or description..." 
                        class="w-full px-3.5 py-2 rounded-lg bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Date Field Selector -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Filter By Date</label>
                    <select name="date_field" class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="created_at" {{ request('date_field', 'created_at') === 'created_at' ? 'selected' : '' }}>Created Date</option>
                        <option value="preferred_date" {{ request('date_field') === 'preferred_date' ? 'selected' : '' }}>Preferred Date</option>
                    </select>
                </div>

                <!-- From Date -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" 
                        class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- To Date -->
                <div class="md:col-span-2">
                    <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" 
                        class="w-full px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Submit & Clear Buttons -->
                <div class="md:col-span-2 flex items-end space-x-2 pt-2 md:pt-5">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition shadow-md shadow-indigo-600/20">Filter</button>
                    @if(request()->hasAny(['search', 'status', 'from_date', 'to_date', 'date_preset', 'date_field']))
                        <a href="{{ route('admin.requests.index') }}" class="px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">Reset</a>
                    @endif
                </div>
            </div>

            <!-- Row 2: Status Pills & Quick Date Presets -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 border-t border-slate-800/80 pt-3">
                <!-- Status Pills -->
                <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                    <span class="text-[11px] font-semibold text-slate-500 mr-1 uppercase tracking-wider">Status:</span>
                    @php
                        $currentStatus = request('status');
                        $baseQueryParams = request()->except(['status', 'page']);
                    @endphp
                    <a href="{{ route('admin.requests.index', $baseQueryParams) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ !$currentStatus ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">All</a>
                    <a href="{{ route('admin.requests.index', array_merge($baseQueryParams, ['status' => 'submitted'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentStatus == 'submitted' ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Submitted</a>
                    <a href="{{ route('admin.requests.index', array_merge($baseQueryParams, ['status' => 'assigned'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentStatus == 'assigned' ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Assigned</a>
                    <a href="{{ route('admin.requests.index', array_merge($baseQueryParams, ['status' => 'in_progress'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentStatus == 'in_progress' ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">In Progress</a>
                    <a href="{{ route('admin.requests.index', array_merge($baseQueryParams, ['status' => 'completed'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentStatus == 'completed' ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Completed</a>
                    <a href="{{ route('admin.requests.index', array_merge($baseQueryParams, ['status' => 'cancelled'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentStatus == 'cancelled' ? 'bg-indigo-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Cancelled</a>
                </div>

                <!-- Date Presets -->
                <div class="flex items-center space-x-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
                    <span class="text-[11px] font-semibold text-slate-500 mr-1 uppercase tracking-wider">Quick Date:</span>
                    @php
                        $currentPreset = request('date_preset');
                        $presetBaseParams = request()->except(['date_preset', 'from_date', 'to_date', 'page']);
                    @endphp
                    <a href="{{ route('admin.requests.index', array_merge($presetBaseParams, ['date_preset' => 'today'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentPreset == 'today' ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Today</a>
                    <a href="{{ route('admin.requests.index', array_merge($presetBaseParams, ['date_preset' => 'yesterday'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentPreset == 'yesterday' ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">Yesterday</a>
                    <a href="{{ route('admin.requests.index', array_merge($presetBaseParams, ['date_preset' => 'this_week'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentPreset == 'this_week' ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">This Week</a>
                    <a href="{{ route('admin.requests.index', array_merge($presetBaseParams, ['date_preset' => 'this_month'])) }}" class="px-2.5 py-1 rounded-md text-xs font-medium {{ $currentPreset == 'this_month' ? 'bg-emerald-600 text-white' : 'bg-slate-800/80 text-slate-400 hover:text-white' }}">This Month</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Request #</th>
                    <th class="px-6 py-4">Service</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Type</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Lifecycle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($requests as $r)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4 font-bold text-white">
                            {{ $r->request_number }}
                            <div class="text-xs text-slate-500">{{ $r->created_at->format('M d, Y H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-200">
                            {{ $r->service->name ?? 'Service' }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            <div>{{ $r->customer->name }}</div>
                            <div class="text-slate-500">{{ $r->customer->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $r->request_type->value === 'broadcast' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                {{ ucfirst($r->request_type->value) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                {{ ucfirst(str_replace('_', ' ', $r->status->value)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.requests.show', $r->id) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/30">Inspect Lifecycle →</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No service requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $requests->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
