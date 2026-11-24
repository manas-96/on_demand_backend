@extends('layouts.admin')

@section('title', 'Customers Management')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Customer Registry</h1>
            <p class="text-xs text-slate-400">Manage registered platform customers, account statuses, and addresses.</p>
        </div>
        <a href="{{ route('admin.customers.create') }}" 
           class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Add Customer</span>
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter/Search Bar -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="flex items-center space-x-3 w-full max-w-md">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer name, email or phone..." 
                class="w-full px-4 py-2 rounded-lg bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold">Search</button>
        </form>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Phone</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4">Joined Date</th>
                    <th class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($customers as $c)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4">
                            <div class="font-bold text-white">{{ $c->name }}</div>
                            <div class="text-xs text-slate-400">{{ $c->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-slate-300">{{ $c->phone ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $c->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                {{ $c->is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-400">{{ $c->created_at->inDisplayTimezone()->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.customers.show', $c->id) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-200 border border-slate-700">View</a>
                            <form method="POST" action="{{ route('admin.customers.toggle-status', $c->id) }}" class="inline-block">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg {{ $c->is_active ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' }} text-xs font-medium">
                                    {{ $c->is_active ? 'Suspend' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $customers->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
