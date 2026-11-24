@extends('layouts.admin')

@section('title', 'Manage Privacy Policy Content')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Privacy Policy Editor</h1>
            <p class="text-xs text-slate-400">View and edit the platform's public Privacy Policy document.</p>
        </div>
        <div>
            <a href="{{ route('privacy-policy') }}" target="_blank" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-400 font-semibold text-xs transition flex items-center space-x-1.5">
                <span>View Public Page ↗</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm flex items-center space-x-2">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.privacy-policy.update') }}" class="space-y-6">
        @csrf

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Privacy Policy Text (Markdown / Text Format)</h2>
                    <p class="text-xs text-slate-400 mt-1">Updates will instantly reflect on the public website and API endpoints.</p>
                </div>
                <div class="text-xs text-slate-500">
                    Last Updated: <strong class="text-slate-300">{{ $settings->updated_at?->inDisplayTimezone()->format('M d, Y H:i') }}</strong>
                </div>
            </div>

            <div>
                <textarea id="privacy_policy" name="privacy_policy" rows="22" required
                          class="w-full bg-slate-950 border border-slate-800 text-slate-200 rounded-xl p-4 text-sm font-mono leading-relaxed focus:outline-none focus:border-amber-400 transition whitespace-pre-wrap">{{ old('privacy_policy', $settings->privacy_policy) }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-2">
                <a href="{{ route('admin.settings.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
                    ← Back to Settings
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                    Save Privacy Policy Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
