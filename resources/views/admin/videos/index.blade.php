@extends('layouts.admin')

@section('title', 'Promotional Videos')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Promotional Videos 🎬</h2>
            <p class="text-xs text-slate-400 mt-1">Manage promotional videos, service demo walk-throughs, and YouTube video links for customer app showcase.</p>
        </div>
        <a href="{{ route('admin.videos.create') }}" 
           class="inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Add Video</span>
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <form method="GET" action="{{ route('admin.videos.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by video title or description..." 
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

    <!-- Videos Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Thumbnail</th>
                        <th class="px-6 py-4">Title & Description</th>
                        <th class="px-6 py-4">YouTube Link / ID</th>
                        <th class="px-6 py-4">Category Link</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($videos as $vid)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4">
                                @if($vid->thumbnail_url)
                                    <img src="{{ $vid->thumbnail_url }}" alt="{{ $vid->title }}" class="w-20 h-12 object-cover rounded-lg border border-slate-700 shadow-sm">
                                @else
                                    <div class="w-20 h-12 bg-slate-800 rounded-lg flex items-center justify-center text-xs text-slate-500 border border-slate-700">No Img</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-white">{{ $vid->title }}</div>
                                @if($vid->description)
                                    <div class="text-xs text-slate-400 mt-0.5 max-w-xs truncate">{{ $vid->description }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <a href="https://www.youtube.com/watch?v={{ $vid->youtube_id }}" target="_blank" rel="noopener noreferrer" 
                                   class="inline-flex items-center space-x-1.5 text-xs font-semibold text-rose-400 hover:text-rose-300 bg-rose-500/10 px-2.5 py-1 rounded-lg border border-rose-500/20 transition">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/></svg>
                                    <span>ID: {{ $vid->youtube_id }}</span>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <span class="px-2.5 py-1 rounded-lg bg-amber-400/10 text-amber-300 border border-amber-400/20 font-medium">
                                    {{ $vid->category->name ?? 'All Categories' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" action="{{ route('admin.videos.toggle-status', $vid->id) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $vid->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }} transition">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $vid->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        <span>{{ $vid->is_active ? 'Active' : 'Inactive' }}</span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.videos.edit', $vid->id) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 border border-slate-700 transition">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.videos.destroy', $vid->id) }}" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this promotional video?');">
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
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No promotional videos found. Click <strong>Add Video</strong> to create your first promotional video!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($videos->hasPages())
            <div class="px-6 py-4 border-t border-slate-800/80">
                {{ $videos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
