@extends('layouts.admin')

@section('title', 'Lifecycle Inspector - ' . $serviceRequest->request_number)

@section('content')
<div class="space-y-8">
    
    <!-- Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.requests.index') }}" class="text-xs text-indigo-400 hover:underline mb-1 inline-block">← Back to Requests Registry</a>
            <h1 class="text-2xl font-bold text-white">Request Lifecycle Inspector: {{ $serviceRequest->request_number }}</h1>
            <p class="text-xs text-slate-400">Full end-to-end audit trace from customer creation to payment settlement and review.</p>
        </div>
        <div class="flex items-center gap-3">
            @if($invoice && ($invoice->status->value === 'paid' || $serviceRequest->status->value === 'completed'))
                <a href="{{ route('admin.requests.invoice.download', $serviceRequest->id) }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-2 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Download Invoice 📄</span>
                </a>
            @endif
            <span class="px-3.5 py-1.5 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/30 font-bold text-xs">
                Current Status: {{ strtoupper(str_replace('_', ' ', $serviceRequest->status->value)) }}
            </span>
        </div>
    </div>

@php
    $assignedProvider = $serviceRequest->targetProvider 
        ?? $assignment?->provider 
        ?? $serviceRequest->providerJobRequests->first(function($pjr) {
            $val = is_object($pjr->status) ? $pjr->status->value : $pjr->status;
            return in_array($val, ['selected', 'accepted']);
        })?->provider
        ?? $bids->first(function($b) {
            $val = is_object($b->status) ? $b->status->value : $b->status;
            return $val === 'accepted';
        })?->provider;

    $awardedProviderName = $assignedProvider?->name ?? 'Pending';
    $providerProfile = $assignedProvider?->providerProfile;
    $technician = $assignment?->technician;
    $technicianProfile = $technician?->technicianProfile;

    $isPaid = $invoice && (is_object($invoice->status) ? $invoice->status->value : $invoice->status) === 'paid';
    $commissionRate = (float) ($providerProfile?->commission ?? 10.00);

    $effectiveQuote = $quote ?? $serviceRequest->commercialQuote;
    $quoteLabor = (float) ($effectiveQuote?->labor_subtotal ?? 0.00);
    $quoteMaterial = (float) ($effectiveQuote?->material_subtotal ?? 0.00);
    $quotePreTaxSubtotal = $quoteLabor + $quoteMaterial;

    if ($quotePreTaxSubtotal > 0) {
        $serviceCost = $quotePreTaxSubtotal;
    } elseif ($effectiveQuote && (float) $effectiveQuote->subtotal > 0) {
        $serviceCost = (float) $effectiveQuote->subtotal;
    } else {
        $serviceCost = (float) ($serviceRequest->service?->base_price ?? $invoice?->subtotal ?? 0.00);
    }

    $taxAmount = (float) ($effectiveQuote?->tax ?? $invoice?->tax ?? 0.00);
    $grandTotal = $effectiveQuote ? (float) $effectiveQuote->total : ($serviceCost + $taxAmount);
    $tipAmount = (float) ($invoice?->tip_amount ?? 0.00);

    $upfrontPayment = 250.00;
    $remainingCustomerPayment = max(0.0, $grandTotal - $upfrontPayment);
    $totalCustomerPaymentAfterCompletion = $remainingCustomerPayment + $tipAmount;

    $adminProfit = round($serviceCost * ($commissionRate / 100), 2);
    $providerPayout = round($grandTotal - $adminProfit + $tipAmount, 2);

    $awardedHistory = $history->first(function($h) {
        return in_array($h->new_status, ['awarded', 'assigned', 'in_progress', 'completed']);
    });
    $awardedTime = $awardedHistory?->created_at 
        ?? $serviceRequest->providerJobRequests->firstWhere('status', 'selected')?->updated_at 
        ?? ($assignedProvider ? $serviceRequest->updated_at : null);

    $assignmentTime = $assignment?->assigned_at ?? $assignment?->created_at;
    $inspectionTime = $inspection?->inspected_at ?? $inspection?->created_at;
    $quoteTime = $quote?->created_at;
    $approvalTime = $approvals->last()?->created_at;
    $progressTime = $progress->last()?->created_at;
    $invoiceTime = $invoice?->created_at;
    $paymentTime = $invoice?->payments->last()?->paid_at ?? ($isPaid ? $invoice?->updated_at : null);
    $reviewTime = $review?->created_at;
@endphp

    <!-- 15-Stage Lifecycle Stepper -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Canonical Lifecycle Trace</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 text-xs">
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">1. Customer Request</span>
                    <strong class="text-emerald-400">Created</strong>
                </div>
                <span class="text-[10px] text-slate-400 font-mono block">{{ $serviceRequest->created_at?->inDisplayTimezone()->format('M d, Y H:i') }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">2. Type & Dispatch</span>
                    <strong class="text-white">{{ ucfirst($serviceRequest->request_type->value) }}</strong>
                </div>
                <span class="text-[10px] text-slate-400 font-mono block">{{ $serviceRequest->created_at?->inDisplayTimezone()->format('M d, Y H:i') }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">3. Awarded Provider</span>
                    <strong class="text-indigo-400">{{ $awardedProviderName }}</strong>
                </div>
                <span class="text-[10px] {{ $awardedTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $awardedTime ? $awardedTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">4. Technician Dispatch</span>
                    <strong class="text-white">{{ $assignment?->technician?->name ?? 'Unassigned' }}</strong>
                </div>
                <span class="text-[10px] {{ $assignmentTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $assignmentTime ? $assignmentTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">5. Site Inspection</span>
                    <strong class="{{ $inspection ? 'text-emerald-400' : 'text-slate-500' }}">{{ $inspection ? 'Completed' : 'Pending' }}</strong>
                </div>
                <span class="text-[10px] {{ $inspectionTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $inspectionTime ? $inspectionTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">6. Technical Quote</span>
                    <strong class="{{ $quote ? 'text-emerald-400' : 'text-slate-500' }}">${{ number_format($quote?->total ?? 0, 2) }}</strong>
                </div>
                <span class="text-[10px] {{ $quoteTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $quoteTime ? $quoteTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">7. Customer Approval</span>
                    <strong class="{{ $approvals->count() ? 'text-emerald-400' : 'text-slate-500' }}">{{ $approvals->count() ? 'Approved' : 'Pending' }}</strong>
                </div>
                <span class="text-[10px] {{ $approvalTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $approvalTime ? $approvalTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">8. Progress Exec</span>
                    <strong class="text-indigo-400">{{ $progress->last()?->progress_percentage ?? 0 }}% Done</strong>
                </div>
                <span class="text-[10px] {{ $progressTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $progressTime ? $progressTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">9. Final Invoice</span>
                    <strong class="text-amber-400">{{ $invoice?->invoice_number ?? 'Pending' }}</strong>
                </div>
                <span class="text-[10px] {{ $invoiceTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $invoiceTime ? $invoiceTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">10. Settlement</span>
                    <strong class="{{ $invoice?->status->value === 'paid' ? 'text-emerald-400' : 'text-slate-500' }}">
                        {{ ucfirst($invoice?->status->value ?? 'Unpaid') }}
                        @if($invoice && (float)$invoice->tip_amount > 0)
                            <span class="text-indigo-400 text-[10px] block font-normal">(+${{ number_format($invoice->tip_amount, 2) }} Tip 🎁)</span>
                        @endif
                    </strong>
                </div>
                <span class="text-[10px] {{ $paymentTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $paymentTime ? $paymentTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex flex-col justify-between space-y-2">
                <div>
                    <span class="text-slate-500 block">11. Review Rating</span>
                    <strong class="text-amber-400">{{ $review ? $review->rating . ' ★' : 'No Review' }}</strong>
                </div>
                <span class="text-[10px] {{ $reviewTime ? 'text-slate-400 font-mono' : 'text-slate-600 italic' }} block">{{ $reviewTime ? $reviewTime->format('M d, Y H:i') : 'Pending' }}</span>
            </div>
        </div>
    </div>

    <!-- Inspector Sections Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left: Request Metadata & Bids -->
        <div class="space-y-6">
            <!-- Basic Request Details -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-3 text-sm">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Request & Customer Information</h2>
                <div><span class="text-slate-500">Category:</span> <strong class="text-white">{{ $serviceRequest->category?->name ?? $serviceRequest->service?->name ?? 'General Service' }}</strong></div>
                <div><span class="text-slate-500">Sub Category:</span> <strong class="text-slate-300">{{ $serviceRequest->subCategory?->name ?? 'N/A' }}</strong></div>
                <div><span class="text-slate-500">Customer:</span> <strong class="text-white">{{ $serviceRequest->customer?->name ?? 'N/A' }}</strong> ({{ $serviceRequest->customer?->email ?? 'N/A' }})</div>
                <div><span class="text-slate-500">Address Line:</span> <span class="text-slate-300">{{ $serviceRequest->address_line }}</span></div>
                <div><span class="text-slate-500">GPS Coordinates:</span> <span class="text-slate-400">Lat: {{ $serviceRequest->latitude }}, Lng: {{ $serviceRequest->longitude }}</span></div>
            </div>

            <!-- Provider & Technician Details -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 text-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Provider & Technician Details 🏢👨‍🔧</h2>
                    @if($assignedProvider)
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
                            Assigned
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-[10px] font-bold uppercase">
                            Unassigned
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Service Provider Info -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                        <span class="text-xs font-bold text-indigo-400 block uppercase tracking-wider">Service Provider</span>
                        @if($assignedProvider)
                            <div><span class="text-slate-500">Name:</span> <strong class="text-white">{{ $assignedProvider->name }}</strong></div>
                            <div><span class="text-slate-500">Company:</span> <span class="text-slate-300">{{ $providerProfile?->company_name ?? 'N/A' }}</span></div>
                            <div><span class="text-slate-500">Email:</span> <span class="text-slate-300">{{ $assignedProvider->email }}</span></div>
                            <div><span class="text-slate-500">Phone:</span> <span class="text-slate-300">{{ $assignedProvider->phone ?? 'N/A' }}</span></div>
                            <div><span class="text-slate-500">Commission Rate:</span> <strong class="text-emerald-400">{{ number_format($commissionRate, 2) }}%</strong></div>
                        @else
                            <p class="text-slate-500 italic">No provider assigned yet.</p>
                        @endif
                    </div>

                    <!-- Assigned Technician Info -->
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                        <span class="text-xs font-bold text-indigo-400 block uppercase tracking-wider">Assigned Technician</span>
                        @if($technician)
                            <div><span class="text-slate-500">Name:</span> <strong class="text-white">{{ $technician->name }}</strong></div>
                            <div><span class="text-slate-500">Email:</span> <span class="text-slate-300">{{ $technician->email }}</span></div>
                            <div><span class="text-slate-500">Phone:</span> <span class="text-slate-300">{{ $technician->phone ?? 'N/A' }}</span></div>
                            <div><span class="text-slate-500">Status:</span> <span class="text-emerald-400 font-semibold">{{ ucfirst(str_replace('_', ' ', is_object($assignment?->status) ? $assignment->status->value : ($assignment?->status ?? 'assigned'))) }}</span></div>
                        @else
                            <p class="text-slate-500 italic">No technician assigned yet.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Invoice & Financial Settlement Breakdown -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Invoice & Settlement Financial Breakdown 💳</h2>
                    @if($invoice)
                        <span class="px-2.5 py-1 rounded-full {{ $invoice->status->value === 'paid' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-amber-500/10 border-amber-500/30 text-amber-400' }} border text-[10px] font-bold uppercase">
                            {{ strtoupper($invoice->status->value) }}
                        </span>
                    @endif
                </div>

                @if($invoice)
                    <div class="space-y-3 text-xs">
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                            <div>
                                <span class="text-slate-500 block">Invoice Number:</span>
                                <strong class="text-white text-sm">{{ $invoice->invoice_number }}</strong>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-500 block">Date Issued:</span>
                                <span class="text-slate-300">{{ $invoice->created_at->inDisplayTimezone()->format('M d, Y') }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-slate-500 block">Service Subtotal:</span>
                                <strong class="text-white">${{ number_format($invoice->subtotal, 2) }}</strong>
                            </div>

                            @if((float) $invoice->tip_amount > 0)
                                <div class="p-3 rounded-xl bg-indigo-950/40 border border-indigo-500/30">
                                    <span class="text-indigo-400 font-semibold block flex items-center justify-between">
                                        <span>Technician Tip</span>
                                        <span>🎁</span>
                                    </span>
                                    <strong class="text-indigo-300 text-sm">+${{ number_format($invoice->tip_amount, 2) }}</strong>
                                </div>
                            @endif

                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <span class="text-slate-500 block">Total Settlement Billed:</span>
                                <strong class="text-emerald-400 text-sm">${{ number_format($invoice->total, 2) }}</strong>
                            </div>
                        </div>

                        @if($isPaid)
                            <div class="p-4 rounded-xl bg-slate-950 border border-emerald-500/30 space-y-3">
                                <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Admin Financial Profit Breakdown 📈</span>
                                    <a href="{{ route('admin.requests.invoice.download', $serviceRequest->id) }}" class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 underline flex items-center gap-1">
                                        <span>Download PDF Invoice</span> 📄
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                                    <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                        <span class="text-slate-500 block text-[10px]">Service Cost (excl. Tax/Tip)</span>
                                        <strong class="text-white text-xs">${{ number_format($serviceCost, 2) }}</strong>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                        <span class="text-slate-500 block text-[10px]">Grand Total (incl. Tax)</span>
                                        <strong class="text-white text-xs">${{ number_format($grandTotal, 2) }}</strong>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                        <span class="text-slate-500 block text-[10px]">Commission Rate</span>
                                        <strong class="text-indigo-400 text-xs">{{ number_format($commissionRate, 2) }}%</strong>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-emerald-950/40 border border-emerald-500/30">
                                        <span class="text-emerald-400 block text-[10px] font-semibold">Admin Profit ({{ number_format($commissionRate, 2) }}% × Service Cost)</span>
                                        <strong class="text-emerald-400 text-xs">+${{ number_format($adminProfit, 2) }}</strong>
                                    </div>
                                    <div class="p-2.5 rounded-lg bg-slate-900 border border-slate-800">
                                        <span class="text-slate-500 block text-[10px]">Provider Payout</span>
                                        <strong class="text-slate-200 text-xs">${{ number_format($providerPayout, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($invoice->payments->count())
                            <div class="pt-2 space-y-2">
                                <span class="text-xs font-semibold text-slate-400">Payment Transactions:</span>
                                @foreach($invoice->payments as $payment)
                                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex justify-between items-center text-xs">
                                        <div>
                                            <span class="font-bold text-white">{{ $payment->transaction_id }}</span>
                                            <span class="text-slate-500 block text-[10px]">Method: {{ ucfirst($payment->payment_gateway) }}</span>
                                        </div>
                                        <div class="text-right">
                                            <strong class="text-emerald-400">${{ number_format($payment->amount, 2) }}</strong>
                                            <span class="text-slate-500 block text-[10px]">{{ $payment->paid_at?->inDisplayTimezone()->format('M d, H:i') ?? 'Paid' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-slate-500 text-sm">No invoice generated for this service request yet.</p>
                @endif
            </div>

            <!-- Customer Problem Requirements & Uploaded Photos -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Customer Problem Requirements</h2>
                
                <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                    <span class="text-xs font-semibold text-slate-400 block uppercase tracking-wider">Problem Description & Scope</span>
                    <p class="text-slate-200 text-sm leading-relaxed whitespace-pre-line">{{ $serviceRequest->description ?: 'No detailed description provided by customer.' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-500 block mb-1">Category:</span>
                        <strong class="text-amber-400 font-semibold">{{ $serviceRequest->category?->name ?? $serviceRequest->service?->name ?? 'General Service' }}</strong>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-500 block mb-1">Sub Category:</span>
                        <strong class="text-slate-200 font-semibold">{{ $serviceRequest->subCategory?->name ?? 'N/A' }}</strong>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-500 block mb-1">Preferred Schedule:</span>
                        <strong class="text-white font-semibold">{{ $serviceRequest->preferred_date?->format('M d, Y') ?? 'Flexible' }} {{ $serviceRequest->preferred_start_time ? '('.$serviceRequest->preferred_start_time.')' : '' }}</strong>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-500 block mb-1">Priority Level:</span>
                        <strong class="text-amber-400 uppercase font-semibold">{{ $serviceRequest->priority ?? 'Normal' }}</strong>
                    </div>
                </div>

                <!-- Customer Attached Problem Photos -->
                <div class="pt-2">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Customer Attached Photos</h3>
                    @if($serviceRequest->photos->count())
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($serviceRequest->photos as $photo)
                                <div class="group relative rounded-xl overflow-hidden bg-slate-950 border border-slate-800 aspect-video flex items-center justify-center">
                                    @if(str_starts_with($photo->file_path, 'http://') || str_starts_with($photo->file_path, 'https://'))
                                        <img src="{{ $photo->file_path }}" alt="Problem Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    @elseif(file_exists(public_path('storage/' . $photo->file_path)))
                                        <img src="{{ asset('storage/' . $photo->file_path) }}" alt="Problem Photo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                    @else
                                        <div class="p-3 text-center text-xs text-slate-400 flex flex-col items-center justify-center space-y-1">
                                            <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                            <span class="text-[10px] truncate max-w-[120px]">{{ basename($photo->file_path) }}</span>
                                        </div>
                                    @endif
                                    <a href="{{ str_starts_with($photo->file_path, 'http') ? $photo->file_path : asset('storage/' . $photo->file_path) }}" target="_blank" class="absolute inset-0 bg-slate-950/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold">
                                        View Photo ↗
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/80 text-center">
                            <p class="text-xs text-slate-500">No problem photos attached by customer for this request.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right: Dispatch, Quote & Admin Override -->
        <div class="space-y-6">
            
            <!-- Site Inspection & Quote Breakdown -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Technical Scope Quote Breakdown</h2>
                @if($quote)
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-3 rounded-xl bg-slate-950">Labor Subtotal: <strong>${{ number_format($quote->labor_subtotal, 2) }}</strong></div>
                            <div class="p-3 rounded-xl bg-slate-950">Material Subtotal: <strong>${{ number_format($quote->material_subtotal, 2) }}</strong></div>
                            <div class="p-3 rounded-xl bg-slate-950">Tax (5%): <strong>${{ number_format($quote->tax, 2) }}</strong></div>
                            <div class="p-3 rounded-xl bg-slate-950 text-emerald-400">Grand Total: <strong>${{ number_format($quote->total, 2) }}</strong></div>
                        </div>

                        <div class="space-y-2 pt-2">
                            <span class="text-xs font-semibold text-slate-400">Line Items:</span>
                            @foreach($quote->items as $qItem)
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs flex justify-between">
                                    <span>{{ $qItem->description }} ({{ $qItem->quantity }}x)</span>
                                    <strong class="text-white">${{ number_format($qItem->total_price, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <p class="text-slate-500 text-sm">No post-inspection quote generated yet.</p>
                @endif
            </div>

            <!-- Manual Provider Assignment (When job is not accepted/assigned yet) -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-indigo-400">Manual Provider Assignment 👨‍🔧</h2>
                    @if($serviceRequest->targetProvider || $assignment?->provider)
                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] font-bold uppercase">
                            Assigned: {{ $serviceRequest->targetProvider?->name ?? $assignment?->provider?->name }}
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-[10px] font-bold uppercase">
                            Unassigned / Pending
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400">If a job is not accepted or awarded yet by providers, admin can manually select and assign a service provider.</p>

                <form method="POST" action="{{ route('admin.requests.assign-provider', $serviceRequest->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="provider_id" class="block text-xs font-medium text-slate-300 mb-1">Select Service Provider</label>
                        <select id="provider_id" name="provider_id" required class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
                            <option value="" disabled {{ !($serviceRequest->target_provider_id) ? 'selected' : '' }}>-- Choose Provider --</option>
                            @foreach($providers as $p)
                                <option value="{{ $p->id }}" {{ $serviceRequest->target_provider_id === $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->email }}{{ $p->phone ? ' - ' . $p->phone : '' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition">
                        {{ $serviceRequest->target_provider_id ? 'Re-assign Provider' : 'Manually Assign Provider' }}
                    </button>
                </form>
            </div>

            <!-- Controlled Admin Status Override (Section 79) -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-amber-400">Controlled Admin Override</h2>
                <p class="text-xs text-slate-400">Manually transition the state machine. Every override creates an immutable audit record in `job_status_history`.</p>
                
                <form method="POST" action="{{ route('admin.requests.override-status', $serviceRequest->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="new_status" class="block text-xs font-medium text-slate-300 mb-1">Target Status</label>
                        <select id="new_status" name="new_status" required class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white">
                            @foreach(['submitted', 'matching', 'invited', 'in_negotiation', 'awarded', 'assigned', 'in_progress', 'completed', 'cancelled'] as $st)
                                <option value="{{ $st }}" {{ $serviceRequest->status->value === $st ? 'selected' : '' }}>{{ strtoupper($st) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="reason" class="block text-xs font-medium text-slate-300 mb-1">Mandatory Override Reason</label>
                        <textarea id="reason" name="reason" required rows="2" placeholder="State reason for administrative status override..." class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-600"></textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 px-4 bg-amber-600 hover:bg-amber-500 text-white font-semibold text-xs rounded-xl shadow-lg shadow-amber-600/30 transition">
                        Execute Status Override
                    </button>
                </form>
            </div>

            <!-- Audit Trace History -->
            <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400">Status History Audit Trace</h2>
                <div class="space-y-2 text-xs">
                    @forelse($history as $h)
                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white">{{ $h->changed_by_type }} ({{ $h->changedBy->name ?? 'System' }})</span>
                                <span class="text-slate-500">{{ $h->created_at->inDisplayTimezone()->format('M d, H:i') }}</span>
                            </div>
                            <div class="text-slate-400 mt-1">Status: {{ $h->old_status ?? 'None' }} → <strong class="text-indigo-400">{{ $h->new_status }}</strong></div>
                            @if($h->reason)
                                <div class="text-slate-500 mt-1 italic">"{{ $h->reason }}"</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500 text-xs">No status change history recorded yet.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
