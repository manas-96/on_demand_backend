@extends('layouts.admin')

@section('title', 'Add Promotional Video')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Add Promotional Video 🎬</h2>
            <p class="text-xs text-slate-400 mt-1">Configure YouTube video link, custom thumbnail image, and target category.</p>
        </div>
        <a href="{{ route('admin.videos.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition">
            Back to Videos
        </a>
    </div>

    <!-- Create Form -->
    <form method="POST" action="{{ route('admin.videos.store') }}" enctype="multipart/form-data" class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 space-y-6 backdrop-blur-xl">
        @csrf

        @if($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Video Title -->
            <div class="space-y-2 md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Video Title <span class="text-rose-400">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. How Professional AC Deep Cleaning Works" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- YouTube Video URL -->
            <div class="space-y-2 md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">YouTube Video URL or Video ID <span class="text-rose-400">*</span></label>
                <input type="text" name="video" value="{{ old('video') }}" required placeholder="e.g. https://www.youtube.com/watch?v=fJ9rUzIMcZQ or fJ9rUzIMcZQ" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
                <p class="text-[11px] text-slate-400">Paste any standard YouTube link or 11-character Video ID. High quality thumbnail will be auto-generated if no custom thumbnail is uploaded.</p>
            </div>

            <!-- Category Link -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Service Category Link</label>
                <select name="service_category_id" class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
                    <option value="">General / All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('service_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Display Sort Order -->
            <div class="space-y-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Thumbnail Upload Option -->
            <div class="space-y-2 md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Custom Thumbnail Image (Optional)</label>
                <input type="file" name="thumbnail" accept="image/*" 
                       class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl px-4 py-2.5 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-400 file:text-slate-950 hover:file:bg-amber-300 transition">
                <p class="text-[11px] text-slate-400">Upload custom thumbnail image (PNG, JPG, WEBP max 5MB). If left empty, YouTube thumbnail is used automatically.</p>
            </div>

            <!-- Description -->
            <div class="space-y-2 md:col-span-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Video Description</label>
                <textarea name="description" rows="3" placeholder="Brief description of what this video demonstrates..." 
                          class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">{{ old('description') }}</textarea>
            </div>

            <!-- Active Status Toggle -->
            <div class="space-y-2 md:col-span-2">
                <label class="inline-flex items-center space-x-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                           class="w-5 h-5 rounded bg-slate-950 border-slate-800 text-amber-400 focus:ring-amber-400 focus:ring-offset-slate-900">
                    <span class="text-sm font-semibold text-slate-200">Active (Visible in Customer App)</span>
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-800/80 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.videos.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Cancel</a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                Save Promotional Video
            </button>
        </div>
    </form>
</div>
@endsection
