@extends('layouts.provider-onboarding')

@section('title', 'Verify your email')

@section('content')
    <div class="max-w-md mx-auto space-y-6">
        <div class="text-center space-y-2">
            <div class="text-4xl">✉️</div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">Verify your email</h1>
            <p class="text-sm text-slate-400">Enter the 6-digit code we sent to <strong class="text-slate-200">{{ $email }}</strong>. It expires in 10 minutes.</p>
        </div>

        <div class="bg-slate-800/80 border border-slate-700 rounded-2xl p-6 shadow-2xl shadow-amber-950/20">
            @if ($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('provider.onboarding.verify-email.submit') }}" class="space-y-4">
                @csrf
                <label for="otp" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">Verification code</label>
                <input type="text" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"
                       placeholder="••••••"
                       class="w-full bg-slate-900 border border-slate-700 text-white rounded-xl px-4 py-3 text-center text-2xl tracking-[0.5em] focus:outline-none focus:border-amber-400 transition">
                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-amber-400 to-yellow-500 hover:brightness-110 text-slate-950 font-bold rounded-xl transition text-sm">
                    Verify email
                </button>
            </form>

            <form method="POST" action="{{ route('provider.onboarding.verify-email.resend') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="text-xs text-amber-400 font-semibold hover:underline">Didn't get it? Send a new code</button>
            </form>
        </div>
    </div>
@endsection
