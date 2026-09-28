@extends('layouts.admin')

@section('title', 'Promotional Banners')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Promotional Banners 🎨</h2>
            <p class="text-xs text-slate-400 mt-1">Manage hero carousel banners, promotional offers, and category highlights for customer apps.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}" 
           class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Add Banner</span>
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('admin.banners.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title, subtitle, or highlight text..." 
                   class="w-full bg-slate-900/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
        </div>
        <select name="category_id" onchange="this.form.submit()" class="w-full sm:w-64 bg-slate-900/80 border border-slate-800 text-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold border border-slate-700 transition">
            Filter
        </button>
    </form>

    <!-- Banners Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Image</th>
                        <th class="px-6 py-4">Title & Subtitle</th>
                        <th class="px-6 py-4">Highlight Text</th>
                        <th class="px-6 py-4">Button Text</th>
                        <th class="px-6 py-4">Category Link</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($banners as $banner)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                @if($banner->image)
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="w-16 h-10 object-cover rounded-lg border border-slate-700 shadow-sm">
                                @else
                                    <div class="w-16 h-10 bg-slate-800 rounded-lg flex items-center justify-center text-xs text-slate-500 border border-slate-700">No Img</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">{{ $banner->title }}</div>
                                @if($banner->subtitle)
                                    <div class="text-xs text-slate-400 mt-0.5">{{ $banner->subtitle }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($banner->highlight_text)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-400/10 text-amber-400 border border-amber-400/20">
                                        {{ $banner->highlight_text }}
                                    </span>
                                @else
                                    <span class="text-slate-500 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-xs font-medium text-slate-300">{{ $banner->button_text ?: 'Explore Now' }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div>
                                    <span class="text-slate-400">Cat:</span> 
                                    <strong class="text-amber-300">{{ $banner->category->name ?? 'All Categories' }}</strong>
                                </div>
                                @if($banner->subCategory)
                                    <div class="mt-0.5">
                                        <span class="text-slate-400">Sub:</span> 
                                        <strong class="text-slate-300">{{ $banner->subCategory->name }}</strong>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.banners.toggle-status', $banner->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $banner->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }} transition">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $banner->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        <span>{{ $banner->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.banners.edit', $banner->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 border border-slate-700 transition">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner->id) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this banner?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No promotional banners found. Click <strong>Add Banner</strong> to create your first banner!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($banners->hasPages())
            <div class="px-6 py-4 border-t border-slate-800/80">
                {{ $banners->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
