@extends('layouts.admin')

@section('title', 'Add Promotional Banner')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header Section -->
    <div class="flex items-center justify-between bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Add New Banner 🎨</h2>
            <p class="text-xs text-slate-400 mt-1">Configure promotional text, offer badges, and link to service categories.</p>
        </div>
        <a href="{{ route('admin.banners.index') }}" 
           class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-sm border border-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Banners</span>
        </a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
            <p class="font-bold">Please correct the following errors:</p>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Banner Creation Form -->
    <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Title -->
            <div class="col-span-2 md:col-span-1">
                <label for="title" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Banner Title <span class="text-rose-400">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g., Summer Cooling Special"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Subtitle -->
            <div class="col-span-2 md:col-span-1">
                <label for="subtitle" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Subtitle Description
                </label>
                <input type="text" name="subtitle" id="subtitle" value="{{ old('subtitle') }}" placeholder="e.g., Get top-rated AC servicing at your doorstep"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Highlight Text -->
            <div class="col-span-2 md:col-span-1">
                <label for="highlight_text" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Highlight / Offer Badge Text
                </label>
                <input type="text" name="highlight_text" id="highlight_text" value="{{ old('highlight_text') }}" placeholder="e.g., FLAT 20% OFF or LIMITED TIME"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Button Text -->
            <div class="col-span-2 md:col-span-1">
                <label for="button_text" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Call To Action Button Text
                </label>
                <input type="text" name="button_text" id="button_text" value="{{ old('button_text') }}" placeholder="e.g., Book Now or Explore Services"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Parent Category -->
            <div class="col-span-2 md:col-span-1">
                <label for="service_category_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Target Category ID / Parent Category
                </label>
                <select name="service_category_id" id="service_category_id" class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
                    <option value="">None (Global / All Categories)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" {{ old('service_category_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }} (ID: {{ $parent->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sub Category -->
            <div class="col-span-2 md:col-span-1">
                <label for="sub_category_id" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Target Subcategory ID / Specific Subcategory
                </label>
                <select name="sub_category_id" id="sub_category_id" class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
                    <option value="">None (Whole Category)</option>
                    @foreach($subcategories as $sub)
                        <option value="{{ $sub->id }}" data-parent-id="{{ $sub->parent_id }}" {{ old('sub_category_id') == $sub->id ? 'selected' : '' }}>
                            {{ $sub->name }} (ID: {{ $sub->id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Image File Upload -->
            <div class="col-span-2 md:col-span-1">
                <label for="image" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Banner Image Upload
                </label>
                <input type="file" name="image" id="image" accept="image/*"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-400 file:text-slate-950 hover:file:bg-amber-300 rounded-xl text-sm transition">
                <p class="text-[11px] text-slate-500 mt-1">Recommended aspect ratio: 16:9 or 21:9 banner image (PNG, JPG, WebP, SVG).</p>
            </div>

            <!-- Image URL Preset -->
            <div class="col-span-2 md:col-span-1">
                <label for="image_url" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    OR Image URL Preset
                </label>
                <input type="url" name="image_url" id="image_url" value="{{ old('image_url') }}" placeholder="https://images.unsplash.com/photo-..."
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
                <p class="text-[11px] text-slate-500 mt-1">Use a direct image link if you are not uploading a local file.</p>
            </div>

            <!-- Sort Order -->
            <div class="col-span-2 md:col-span-1">
                <label for="sort_order" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Display Sort Order
                </label>
                <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                       class="w-full bg-slate-950/80 border border-slate-800 text-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <!-- Active Checkbox -->
            <div class="col-span-2 md:col-span-1 flex items-center pt-6">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', true) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-400"></div>
                    <span class="ml-3 text-sm font-semibold text-slate-300">Publish as Active Banner</span>
                </label>
            </div>

        </div>

        <div class="pt-4 border-t border-slate-800/80 flex items-center justify-end space-x-4">
            <a href="{{ route('admin.banners.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold border border-slate-700 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                Save & Create Banner
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const parentSelect = document.getElementById('service_category_id');
        const subSelect = document.getElementById('sub_category_id');
        const allSubOptions = Array.from(subSelect.querySelectorAll('option[data-parent-id]'));

        function filterSubcategories() {
            const selectedParentId = parentSelect.value;
            
            allSubOptions.forEach(option => {
                if (!selectedParentId || option.getAttribute('data-parent-id') === selectedParentId) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                    if (option.selected) {
                        subSelect.value = '';
                    }
                }
            });
        }

        parentSelect.addEventListener('change', filterSubcategories);
        filterSubcategories();
    });
</script>
@endsection
