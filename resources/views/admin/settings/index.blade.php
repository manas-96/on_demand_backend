@extends('layouts.admin')

@section('title', 'System Settings & Audit Trace')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">System Settings & Audit Log Trace</h1>
            <p class="text-xs text-slate-400">Platform operational rules, default fees, tax configuration, and immutable audit logs.</p>
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

    <!-- Active Settings Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Platform Commission Rate</span>
            <div class="text-3xl font-extrabold text-white">{{ number_format($settings->platform_fee, 2) }}%</div>
            <div class="text-xs text-indigo-400">Deducted from gross provider earnings</div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Default Service Tax</span>
            <div class="text-3xl font-extrabold text-white">{{ number_format($settings->tax_rate, 2) }}%</div>
            <div class="text-xs text-emerald-400">Calculated server-side on invoices</div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Minimum Booking Charge</span>
            <div class="text-3xl font-extrabold text-white">${{ number_format($settings->minimum_booking_charge, 2) }}</div>
            <div class="text-xs text-amber-400">Base baseline fee enforced per booking</div>
        </div>
    </div>

    <!-- Edit System Settings Form Panel -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-6">
        <div class="border-b border-slate-800 pb-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Update System Configuration</h2>
            <p class="text-xs text-slate-400 mt-1">Modify global fees, tax rates, and minimum booking charges across the platform.</p>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="platform_fee" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Platform Fee (%) *</label>
                    <input type="number" step="0.01" min="0" max="100" id="platform_fee" name="platform_fee" 
                           value="{{ old('platform_fee', $settings->platform_fee) }}" required
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    <span class="text-[11px] text-slate-500 mt-1 block">Default percentage taken per provider job</span>
                </div>

                <div>
                    <label for="tax_rate" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Service Tax Rate (%) *</label>
                    <input type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate" 
                           value="{{ old('tax_rate', $settings->tax_rate) }}" required
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    <span class="text-[11px] text-slate-500 mt-1 block">Government tax rate applied to customer invoices</span>
                </div>

                <div>
                    <label for="minimum_booking_charge" class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">Minimum Booking Charge ($) *</label>
                    <input type="number" step="0.01" min="0" max="100000" id="minimum_booking_charge" name="minimum_booking_charge" 
                           value="{{ old('minimum_booking_charge', $settings->minimum_booking_charge) }}" required
                           class="w-full bg-slate-950 border border-slate-800 text-white rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-400 transition">
                    <span class="text-[11px] text-slate-500 mt-1 block">Minimum floor charge required per job booking</span>
                </div>
            </div>

            <div class="flex items-center justify-end pt-2">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-950 font-bold text-sm shadow-lg shadow-amber-500/20 hover:brightness-110 transition">
                    Save System Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Trace History Table -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Immutable System Audit Logs</h2>
        
        <div class="rounded-xl bg-slate-950 border border-slate-800 overflow-hidden">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Actor</th>
                        <th class="px-6 py-4">Old Status → New Status</th>
                        <th class="px-6 py-4">Audit Reason / Description</th>
                        <th class="px-6 py-4 text-right">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-800/40 text-xs">
                            <td class="px-6 py-4 font-bold text-white">
                                {{ $log->serviceRequest->request_number ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-200">
                                {{ ucfirst($log->changed_by_type) }} ({{ $log->changedBy->name ?? 'System' }})
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $log->old_status ?? 'Start' }} → <strong class="text-indigo-400">{{ $log->new_status }}</strong>
                            </td>
                            <td class="px-6 py-4 text-slate-400 italic">
                                {{ $log->reason ?? 'Standard workflow state transition' }}
                            </td>
                            <td class="px-6 py-4 text-right text-slate-500">
                                {{ $log->created_at->format('M d, Y H:i:s') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">No system audit log entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-slate-800">
                {{ $auditLogs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
