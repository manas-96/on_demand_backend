@extends('layouts.provider-onboarding')

@section('title', 'Awaiting approval')

@section('content')
    <div class="max-w-lg mx-auto space-y-6 text-center">
        <div class="text-5xl">⏳</div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Your account is under review</h1>
        <p class="text-sm text-slate-400">
            Thanks for registering <strong class="text-slate-200">{{ $providerProfile?->company_name }}</strong>.
            Our team is reviewing your details. We'll email you as soon as your account is approved — usually within one business day.
        </p>

        <div class="text-left bg-slate-800/80 border border-slate-700 rounded-2xl p-6 space-y-3 text-sm">
            <div class="flex items-center gap-3"><span class="text-emerald-400">✓</span><span class="text-slate-300">Account created</span></div>
            <div class="flex items-center gap-3"><span class="text-emerald-400">✓</span><span class="text-slate-300">Email verified</span></div>
            <div class="flex items-center gap-3"><span class="text-amber-400">●</span><span class="text-slate-100 font-semibold">Admin approval</span></div>
            <div class="flex items-center gap-3"><span class="text-slate-600">○</span><span class="text-slate-500">Start receiving jobs</span></div>
        </div>

        <p class="text-xs text-slate-500">
            Questions? <a href="{{ route('support') }}" class="text-amber-400 hover:underline">Contact support</a>
        </p>
    </div>
@endsection
