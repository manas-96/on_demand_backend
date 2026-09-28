@extends('layouts.admin')

@section('title', $category->name . ' - Subcategories')

@section('content')
<div class="space-y-6">
    <!-- Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.services.index') }}" class="hover:text-amber-400 transition">Service Categories</a>
                <span>&rarr;</span>
                <span class="text-white font-medium">{{ $category->name }}</span>
            </div>
            <h2 class="text-xl font-bold text-white tracking-tight">{{ $category->name }}</h2>
            <p class="text-xs text-slate-400 mt-1">Manage subcategories under {{ $category->name }}.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.services.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold border border-slate-700 transition">
                Back to Categories
            </a>
            <a href="{{ route('admin.services.create', ['parent_id' => $category->id]) }}" 
               class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Add Subcategory</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Subcategories Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="px-6 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white">Subcategories ({{ $category->subcategories->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Image</th>
                        <th class="px-6 py-4">Subcategory Name</th>
                        <th class="px-6 py-4">Min. Charge</th>
                        <th class="px-6 py-4">Slug Identifier</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($category->subcategories as $subcategory)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                @if($subcategory->image)
                                    @if(Str::startsWith($subcategory->image, ['http://', 'https://']))
                                        <img src="{{ $subcategory->image }}" alt="{{ $subcategory->name }}" class="w-10 h-10 object-cover rounded-xl border border-slate-700 shadow-sm">
                                    @else
                                        <img src="{{ asset('storage/' . $subcategory->image) }}" alt="{{ $subcategory->name }}" class="w-10 h-10 object-cover rounded-xl border border-slate-700 shadow-sm">
                                    @endif
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-amber-400/10 border border-amber-400/20 flex items-center justify-center text-amber-400 text-xs font-bold">
                                        {{ strtoupper(substr($subcategory->name, 0, 2)) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-bold text-white">
                                {{ $subcategory->name }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-emerald-400">
                                ${{ number_format($subcategory->effective_minimum_booking_charge, 2) }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                {{ $subcategory->slug }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $subcategory->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                                    {{ $subcategory->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.services.edit', $subcategory->id) }}" class="inline-block text-xs font-semibold px-3 py-1.5 rounded-lg border border-slate-700 text-slate-300 hover:bg-slate-800 transition">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.services.toggle-status', $subcategory->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg border {{ $subcategory->is_active ? 'border-rose-500/30 text-rose-400 hover:bg-rose-500/10' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10' }} transition">
                                        {{ $subcategory->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                No subcategories added under {{ $category->name }} yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
