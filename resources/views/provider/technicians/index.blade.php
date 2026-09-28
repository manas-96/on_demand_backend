@extends('layouts.provider')

@section('title', 'Fleet Technicians')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Fleet Technicians</h2>
            <p class="text-xs text-slate-400 mt-1">Manage technicians employed by your company. Only your own technicians are listed here.</p>
        </div>
        <a href="{{ route('provider.technicians.create') }}" 
           class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Add Technician</span>
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search Bar -->
    <form method="GET" action="{{ route('provider.technicians.index') }}" class="flex items-center gap-3">
        <div class="relative flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or phone..." 
                   class="w-full bg-slate-900/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold border border-slate-700 transition">
            Search
        </button>
    </form>

    <!-- Technicians Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Technician</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4">Qualification</th>
                        <th class="px-6 py-4">Duty Status</th>
                        <th class="px-6 py-4">Account Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($technicians as $technician)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 font-bold">
                                    {{ strtoupper(substr($technician->user->name ?? 'T', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-bold text-white">{{ $technician->user->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $technician->user->email }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $technician->user->phone ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $technician->qualification ?? 'General Technician' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $technician->duty_status === 'on_duty' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $technician->duty_status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $technician->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                    {{ $technician->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="{{ route('provider.technicians.toggle-status', $technician->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg border {{ $technician->is_active ? 'border-rose-500/30 text-rose-400 hover:bg-rose-500/10' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10' }} transition">
                                        {{ $technician->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No technicians found in your company fleet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($technicians->hasPages())
            <div class="px-6 py-4 border-t border-slate-800/80">
                {{ $technicians->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
