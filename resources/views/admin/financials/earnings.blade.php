@extends('layouts.admin')

@section('title', 'Provider Earnings & Platform Fees')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Provider Earnings & Platform Fee Ledger</h1>
            <p class="text-xs text-slate-400">Immutable financial ledger records of gross job totals, platform commission deductions, and net payouts.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Provider</th>
                    <th class="px-6 py-4">Request #</th>
                    <th class="px-6 py-4">Gross Amount</th>
                    <th class="px-6 py-4">Platform Fee (10%)</th>
                    <th class="px-6 py-4">Net Provider Earnings</th>
                    <th class="px-6 py-4 text-right">Settlement Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($earnings as $e)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4 font-bold text-white">
                            {{ $e->provider->name }}
                        </td>
                        <td class="px-6 py-4 font-medium text-slate-300">
                            {{ $e->serviceRequest->request_number }}
                        </td>
                        <td class="px-6 py-4 font-semibold text-white">
                            ${{ number_format($e->gross_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-rose-400 font-semibold">
                            -${{ number_format($e->platform_fee, 2) }}
                        </td>
                        <td class="px-6 py-4 text-emerald-400 font-extrabold text-base">
                            ${{ number_format($e->net_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-right text-xs text-slate-400">
                            {{ $e->created_at->format('M d, Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-slate-500">No earnings records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $earnings->links() }}
        </div>
    </div>
</div>
@endsection
