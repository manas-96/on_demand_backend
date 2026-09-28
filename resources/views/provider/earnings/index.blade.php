@extends('layouts.provider')

@section('title', 'Earnings & Financial Statements')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800/80 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Financial Earnings Dashboard</h2>
            <p class="text-xs text-slate-400 mt-1">Real-time revenue statements, platform fees, and net company payouts.</p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold uppercase flex items-center space-x-1.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Live Financial Auto-Sync</span>
            </span>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-800/80 backdrop-blur-xl space-y-4">
        <form method="GET" action="{{ route('provider.earnings.index') }}" class="space-y-4">
            <!-- Preset Period Buttons -->
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mr-1">Period:</span>
                    @foreach(['all' => 'All Time', 'today' => 'Today', 'this_week' => 'This Week', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $key => $label)
                        <a href="{{ route('provider.earnings.index', array_merge(request()->except(['page', 'preset', 'from_date', 'to_date']), ['preset' => $key])) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold transition border {{ request('preset', 'all') === $key ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:bg-slate-800 hover:text-slate-200' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Custom Date Range & Search Input Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-2 text-xs">
                <div>
                    <label for="from_date" class="block text-slate-400 text-[11px] font-semibold mb-1 uppercase">From Date</label>
                    <input type="date" id="from_date" name="from_date" value="{{ request('from_date') }}" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label for="to_date" class="block text-slate-400 text-[11px] font-semibold mb-1 uppercase">To Date</label>
                    <input type="date" id="to_date" name="to_date" value="{{ request('to_date') }}" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div>
                    <label for="search" class="block text-slate-400 text-[11px] font-semibold mb-1 uppercase">Search Request / Customer</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="e.g. SR-20260927-000001 or Customer name" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition">
                </div>
                <div class="flex items-end space-x-2">
                    <button type="submit" class="w-full py-2 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs uppercase tracking-wider transition shadow-md">
                        Apply Filter
                    </button>
                    @if(request()->anyFilled(['preset', 'from_date', 'to_date', 'search']))
                        <a href="{{ route('provider.earnings.index') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs border border-slate-700 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Extended Financial Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</div>
            <div class="text-2xl font-extrabold text-white mt-1">${{ number_format($totalGross, 2) }}</div>
            <div class="text-[10px] text-slate-500">Total customer paid invoices</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Platform Commission</div>
            <div class="text-2xl font-extrabold text-amber-400 mt-1">${{ number_format($totalPlatformFee, 2) }}</div>
            <div class="text-[10px] text-slate-500">Platform fee deducted</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Net Earnings</div>
            <div class="text-2xl font-extrabold text-emerald-400 mt-1">${{ number_format($totalNet, 2) }}</div>
            <div class="text-[10px] text-slate-500">Net payout earnings</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Completed Paid Jobs</div>
            <div class="text-2xl font-extrabold text-indigo-400 mt-1">{{ number_format($totalPaidJobs) }}</div>
            <div class="text-[10px] text-slate-500">Paid service requests</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 backdrop-blur-xl space-y-1">
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Avg Net / Job</div>
            <div class="text-2xl font-extrabold text-blue-400 mt-1">${{ number_format($avgEarning, 2) }}</div>
            <div class="text-[10px] text-slate-500">Average net earning</div>
        </div>
    </div>

    <!-- Earnings History Table -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl overflow-hidden backdrop-blur-xl">
        <div class="px-6 py-4 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="font-bold text-white text-sm">Detailed Payout Statements</h3>
            <span class="text-xs text-slate-400 font-normal">Showing {{ $earnings->firstItem() ?? 0 }} - {{ $earnings->lastItem() ?? 0 }} of {{ $earnings->total() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/60 text-xs text-slate-400 uppercase tracking-wider border-b border-slate-800/80">
                    <tr>
                        <th class="px-6 py-4">Request #</th>
                        <th class="px-6 py-4">Customer</th>
                        <th class="px-6 py-4 text-right">Gross Total</th>
                        <th class="px-6 py-4 text-right">Platform Fee</th>
                        <th class="px-6 py-4 text-right">Net Payout</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                        <th class="px-6 py-4 text-right">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($earnings as $earning)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-4 font-bold text-white">
                                <a href="{{ route('provider.requests.show', $earning->service_request_id) }}" class="text-indigo-400 hover:underline">
                                    {{ $earning->serviceRequest->request_number ?? 'SR-'.$earning->service_request_id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                {{ $earning->serviceRequest->customer->name ?? 'Customer' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-white">
                                ${{ number_format($earning->gross_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-amber-400">
                                -${{ number_format($earning->platform_fee, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-400 text-base">
                                ${{ number_format($earning->net_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('provider.requests.invoice.download', $earning->service_request_id) }}" target="_blank" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold border border-slate-700 transition inline-flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <span>Invoice</span>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-400 text-xs">
                                {{ $earning->created_at->format('M d, Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                No earnings statements found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($earnings->hasPages())
            <div class="px-6 py-4 border-t border-slate-800/80">
                {{ $earnings->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
