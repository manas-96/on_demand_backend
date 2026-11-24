@extends('layouts.admin')

@section('title', 'Promotional Notifications')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Promotional Push Notifications</h1>
            <p class="text-xs text-slate-400">Broadcast marketing offers, announcements, and push notifications to customer devices.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6">
        <form action="{{ route('admin.notifications.send') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-xs font-semibold uppercase text-slate-400 mb-2">Notification Title *</label>
                <input type="text" name="title" id="title" required value="{{ old('title') }}" placeholder="e.g. Special Weekend Offer!" class="w-full rounded-xl bg-slate-950 border border-slate-800 text-white px-4 py-3 text-sm focus:border-amber-500 focus:outline-none">
                @error('title')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="body" class="block text-xs font-semibold uppercase text-slate-400 mb-2">Notification Message Body *</label>
                <textarea name="body" id="body" rows="3" required placeholder="e.g. Get 20% off your next AC repair service this weekend." class="w-full rounded-xl bg-slate-950 border border-slate-800 text-white px-4 py-3 text-sm focus:border-amber-500 focus:outline-none">{{ old('body') }}</textarea>
                @error('body')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="customer_ids" class="block text-xs font-semibold uppercase text-slate-400 mb-2">Target Customers (Leave blank to select ALL customers)</label>
                <select name="customer_ids[]" id="customer_ids" multiple class="w-full rounded-xl bg-slate-950 border border-slate-800 text-white px-4 py-3 text-sm focus:border-amber-500 focus:outline-none h-40">
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ in_array($c->id, old('customer_ids', [])) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->email }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Hold Ctrl/Cmd to select multiple specific customers. If none selected, broadcast will reach all active customers.</p>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-sm font-bold transition duration-150 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    Send Push Notification
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
