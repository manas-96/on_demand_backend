@extends('layouts.admin')

@section('title', 'Email Diagnostics & Testing')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Email Diagnostics & Transport Testing</h1>
            <p class="text-xs text-slate-400">Inspect active mailer parameters and send live test emails across all 6 system notification templates.</p>
        </div>
        <div class="flex items-center space-x-2">
            @if($smtpConfig['mailer'] === 'sendmail')
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    ● Local Sendmail Transport Active
                </span>
            @else
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $smtpConfig['password_configured'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                    {{ $smtpConfig['password_configured'] ? '● SMTP Password Configured' : '○ Password Missing' }}
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium flex items-start space-x-3">
            <svg class="w-5 h-5 shrink-0 text-emerald-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <span class="font-bold block">Delivery Initiated / Successful!</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 text-sm font-medium flex items-start space-x-3">
            <svg class="w-5 h-5 shrink-0 text-rose-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <span class="font-bold block text-rose-400">Email Delivery Failed</span>
                <span class="block mt-1 font-mono text-xs bg-slate-950 p-2.5 rounded-lg border border-slate-800 text-rose-300 overflow-x-auto">{{ session('error') }}</span>
                <div class="mt-2 text-xs text-slate-400 space-y-1">
                    <p>• Verify your active mail driver settings in your <code>.env</code> file.</p>
                    <p>• If using local sendmail, ensure local MTA (Postfix) is configured with an authorized relayhost.</p>
                    <p>• Run <code>php artisan optimize:clear</code> after editing <code>.env</code> settings.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Transport Configuration Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Active Transport -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Active Transport</span>
            <span class="text-sm font-bold text-white block truncate uppercase">{{ $smtpConfig['mailer'] }}</span>
            @if($smtpConfig['mailer'] === 'sendmail')
                <span class="text-xs text-blue-400 font-mono block truncate" title="{{ $smtpConfig['sendmail_path'] }}">{{ $smtpConfig['sendmail_path'] }}</span>
            @else
                <span class="text-xs text-amber-400 font-mono block">{{ $smtpConfig['host'] }}:{{ $smtpConfig['port'] }}</span>
            @endif
        </div>

        <!-- Sender Address -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">From Address</span>
            <span class="text-sm font-bold text-white block truncate">{{ $smtpConfig['from_address'] }}</span>
            <span class="text-xs text-slate-400 block truncate">{{ $smtpConfig['from_name'] }}</span>
        </div>

        <!-- Queue Mode -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Queue Driver</span>
            <span class="text-sm font-bold text-white block uppercase">{{ $smtpConfig['queue_connection'] }}</span>
            <span class="text-xs text-slate-400 block">{{ $smtpConfig['queue_connection'] === 'sync' ? 'Instant Delivery' : 'Requires queue worker' }}</span>
        </div>

        <!-- Socket Timeout -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Socket Timeout</span>
            <span class="text-sm font-bold text-white block">{{ $smtpConfig['timeout'] }} seconds</span>
            <span class="text-xs text-emerald-400 block">Non-blocking active</span>
        </div>
    </div>

    <!-- Main Test Form & Guide -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Interactive Form (2 cols) -->
        <div class="lg:col-span-2 p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
            <div class="border-b border-slate-800 pb-4">
                <h2 class="text-lg font-bold text-white">Send Test Email</h2>
                <p class="text-xs text-slate-400">Select a mailable template and specify a target recipient email address.</p>
            </div>

            <form method="POST" action="{{ route('admin.email-test.send') }}" class="space-y-4">
                @csrf

                <!-- Recipient Email -->
                <div>
                    <label for="recipient_email" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Recipient Email Address <span class="text-amber-400">*</span>
                    </label>
                    <input type="email" id="recipient_email" name="recipient_email" 
                        value="{{ old('recipient_email', auth()->user()->email) }}" 
                        placeholder="admin@example.com" 
                        required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition shadow-md">
                    @error('recipient_email')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Template Selector -->
                <div>
                    <label for="template_type" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Email Template <span class="text-amber-400">*</span>
                    </label>
                    <select id="template_type" name="template_type" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-amber-400 transition shadow-md">
                        <option value="general" {{ old('template_type') == 'general' ? 'selected' : '' }}>
                            🛠️ General SMTP Diagnostic Test (Connection & Transport Check)
                        </option>
                        <option value="booking_created_customer" {{ old('template_type') == 'booking_created_customer' ? 'selected' : '' }}>
                            📋 Booking Request Confirmed (Customer Notification)
                        </option>
                        <option value="service_request_created" {{ old('template_type') == 'service_request_created' ? 'selected' : '' }}>
                            📢 Service Request Created (Provider Notification)
                        </option>
                        <option value="request_accepted" {{ old('template_type') == 'request_accepted' ? 'selected' : '' }}>
                            ✅ Request Accepted / Tech Assigned (Customer Notification)
                        </option>
                        <option value="technician_assigned" {{ old('template_type') == 'technician_assigned' ? 'selected' : '' }}>
                            🧰 Technician Assigned (Technician Notification)
                        </option>
                        <option value="quote_approved" {{ old('template_type') == 'quote_approved' ? 'selected' : '' }}>
                            📄 Quote Approved by Provider (Customer Notification)
                        </option>
                        <option value="quote_accepted" {{ old('template_type') == 'quote_accepted' ? 'selected' : '' }}>
                            🤝 Quote Accepted by Customer (Provider Notification)
                        </option>
                        <option value="booking_cancelled" {{ old('template_type') == 'booking_cancelled' ? 'selected' : '' }}>
                            🚫 Booking Cancelled (Customer / Provider / Tech Notification)
                        </option>
                        <option value="booking_completed" {{ old('template_type') == 'booking_completed' ? 'selected' : '' }}>
                            🎉 Booking Completed (Customer / Provider / Tech Notification)
                        </option>
                    </select>
                    @error('template_type')
                        <span class="text-xs text-rose-400 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Custom Subject (Optional) -->
                <div>
                    <label for="custom_subject" class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1.5">
                        Custom Subject Line <span class="text-slate-500 font-normal lowercase">(optional)</span>
                    </label>
                    <input type="text" id="custom_subject" name="custom_subject" 
                        value="{{ old('custom_subject') }}" 
                        placeholder="e.g. Test SMTP delivery to company account" 
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-amber-400 transition shadow-md">
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" 
                        class="w-full md:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Send Test Email</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Diagnostic Guide & Tips (1 col) -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-amber-400 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Troubleshooting Guide</span>
            </h3>

            <div class="space-y-3 text-xs text-slate-300">
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                    <span class="font-bold text-white block">1. Check Spam / Junk Folder</span>
                    <p class="text-slate-400">When testing from localhost, mail providers like Gmail or Outlook may route messages to your spam/junk folder.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                    <span class="font-bold text-white block">2. Queue Connection (`QUEUE_CONNECTION`)</span>
                    <p class="text-slate-400">If set to <code>sync</code>, emails deliver synchronously. If set to <code>database</code>, run <code>php artisan queue:work</code> in your console.</p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                    <span class="font-bold text-white block">3. cPanel cPHulk Lockout</span>
                    <p class="text-slate-400">If getting <code>535 Authentication</code> errors, reset your email password in cPanel to clear security blocks.</p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
