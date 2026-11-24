@extends('layouts.admin')

@section('title', 'Field Technicians')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Field Technicians Overview</h1>
            <p class="text-xs text-slate-400">Monitor technicians employed across all provider organizations and their duty status.</p>
        </div>
    </div>

    <!-- Filter/Search Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
        <form method="GET" action="{{ route('admin.technicians.index') }}" class="flex items-center space-x-3 w-full max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search technician, email or provider name..." 
                class="w-full px-4 py-2 rounded-lg bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Technician</th>
                    <th class="px-6 py-4">Provider Company</th>
                    <th class="px-6 py-4">Qualification</th>
                    <th class="px-6 py-4">Duty Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($technicians as $tech)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4">
                            <div class="font-bold text-white">{{ $tech->user->name }}</div>
                            <div class="text-xs text-slate-400">{{ $tech->user->email }} • {{ $tech->user->phone ?? 'No Phone' }}</div>
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-200">
                            <div>{{ $tech->providerProfile->company_name ?? $tech->provider->name ?? 'Provider Org' }}</div>
                            @if(isset($tech->providerProfile->company_name) && isset($tech->provider->name) && $tech->providerProfile->company_name !== $tech->provider->name)
                                <div class="text-xs text-slate-400">{{ $tech->provider->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-400">
                            {{ $tech->qualification ?? 'General Technician' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $tech->duty_status === 'on_duty' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                                {{ ucfirst(str_replace('_', ' ', $tech->duty_status)) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">No technicians registered.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $technicians->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
