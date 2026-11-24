@extends('layouts.admin')

@section('title', 'Add Service Category')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Add Service Category</h2>
            <p class="text-xs text-slate-400 mt-1">Define a top-level service category with name and image for customer bookings.</p>
        </div>
        <a href="{{ route('admin.services.index') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold border border-slate-700 transition">
            &larr; Back to Categories
        </a>
    </div>

    <!-- Form Panel -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 md:p-8 backdrop-blur-xl space-y-6">
        @if ($errors->any())
            <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <div>• {{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.services.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label for="parent_id" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Parent Category (Optional)</label>
                <select id="parent_id" name="parent_id" 
                        class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    <option value="">-- None (Create Top-Level Parent Category) --</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" {{ (string) old('parent_id', $selectedParentId ?? '') === (string) $parent->id ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Select a parent category to create a subcategory under it, or leave empty for a main category.</p>
            </div>

            <div>
                <label for="name" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Category / Subcategory Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Plumbing, Refrigeration Repairs, House Cleaning" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <div>
                <label for="minimum_booking_charge" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Minimum Booking Charge ($) *</label>
                <input type="number" step="0.01" min="0" id="minimum_booking_charge" name="minimum_booking_charge" value="{{ old('minimum_booking_charge', '0.00') }}" required placeholder="e.g. 250.00" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                <p class="text-[11px] text-slate-500 mt-1">Upfront minimum charge paid by customer when booking this category (adjusted on final bill).</p>
            </div>

            <div>
                <label for="tags" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Search Tags (Comma-Separated)</label>
                <input type="text" id="tags" name="tags" value="{{ old('tags') }}" placeholder="e.g. pipe, leak, tap, basin, plumber" 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                <p class="text-[11px] text-slate-500 mt-1">Keywords or search tags that customers can search in the mobile app to find this category.</p>
            </div>

            <div>
                <label for="image" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Category Image Upload</label>
                <input type="file" id="image" name="image" accept="image/*"
                       class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-amber-400 transition file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-400/20 file:text-amber-400 hover:file:bg-amber-400/30">
                <p class="text-[11px] text-slate-500 mt-1">Upload JPEG, PNG, WEBP, or SVG image (max 2MB).</p>
            </div>

            <div class="relative py-1 flex items-center">
                <div class="flex-grow border-t border-slate-800"></div>
                <span class="flex-shrink mx-4 text-xs font-bold text-slate-500 uppercase">OR Image URL</span>
                <div class="flex-grow border-t border-slate-800"></div>
            </div>

            <div>
                <label for="image_url" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Category Image URL</label>
                <input type="url" id="image_url" name="image_url" value="{{ old('image_url') }}" placeholder="https://images.unsplash.com/photo-..." 
                       class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
            </div>

            <div class="pt-4 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.services.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-sm hover:bg-slate-700 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                    Create Category
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
