@extends('layouts.admin')

@section('title', 'Invoices & Ledger')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Invoices & Payment Transactions</h1>
            <p class="text-xs text-slate-400">Authoritative financial records, tax, discounts, and payment gateway logs.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-slate-900 border border-slate-800 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-950 text-xs uppercase font-semibold text-slate-400 border-b border-slate-800">
                <tr>
                    <th class="px-6 py-4">Invoice Number</th>
                    <th class="px-6 py-4">Customer</th>
                    <th class="px-6 py-4">Provider</th>
                    <th class="px-6 py-4">Subtotal / Tax / Total</th>
                    <th class="px-6 py-4">Payment Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($invoices as $inv)
                    <tr class="hover:bg-slate-800/40">
                        <td class="px-6 py-4 font-bold text-white">
                            {{ $inv->invoice_number }}
                            <div class="text-xs text-slate-500">{{ $inv->created_at->format('M d, Y H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            {{ $inv->customer->name }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            {{ $inv->provider->name }}
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-300">
                            <div>Subtotal: ${{ number_format($inv->subtotal, 2) }} • Tax: ${{ number_format($inv->tax, 2) }}</div>
                            <div class="text-sm font-extrabold text-emerald-400 mt-0.5">Total: ${{ number_format($inv->total, 2) }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $inv->status->value === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20' }}">
                                {{ ucfirst($inv->status->value) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">No invoices generated yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-800">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
