<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomerApproval;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\JobStatusHistory;
use App\Models\ProviderBid;
use App\Models\ProviderJobRequest;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BusinessTime;
use App\Services\EmailNotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = ServiceRequest::with(['service', 'customer', 'targetProvider']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Datewise filtering
        $dateField = $request->get('date_field', 'created_at');
        if (! in_array($dateField, ['created_at', 'preferred_date'])) {
            $dateField = 'created_at';
        }

        $datePreset = $request->get('date_preset');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        // Presets and typed dates are business-local calendar days (DISPLAY_TIMEZONE).
        $localNow = BusinessTime::now();

        if ($datePreset) {
            switch ($datePreset) {
                case 'today':
                    $fromDate = $localNow->toDateString();
                    $toDate = $localNow->toDateString();
                    break;
                case 'yesterday':
                    $fromDate = $localNow->copy()->subDay()->toDateString();
                    $toDate = $localNow->copy()->subDay()->toDateString();
                    break;
                case 'this_week':
                    $fromDate = $localNow->copy()->startOfWeek()->toDateString();
                    $toDate = $localNow->copy()->endOfWeek()->toDateString();
                    break;
                case 'this_month':
                    $fromDate = $localNow->copy()->startOfMonth()->toDateString();
                    $toDate = $localNow->copy()->endOfMonth()->toDateString();
                    break;
                case 'last_month':
                    $fromDate = $localNow->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                    $toDate = $localNow->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                    break;
            }
        }

        if ($dateField === 'preferred_date') {
            // Wall-clock date column: compare as-is.
            if ($fromDate) {
                $query->whereDate($dateField, '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate($dateField, '<=', $toDate);
            }
        } elseif ($fromDate || $toDate) {
            // UTC timestamp column: convert the local days to a UTC range.
            $query->whereBetween($dateField, BusinessTime::utcRangeForLocalDates($fromDate ?: '1970-01-01', $toDate ?: '2999-12-31'));
        }

        $requests = $query->latest()->paginate(15);

        return view('admin.requests.index', compact('requests', 'fromDate', 'toDate', 'datePreset', 'dateField'));
    }

    /**
     * Complete 15-stage lifecycle inspector view for a service request.
     */
    public function show(int $id): View
    {
        $serviceRequest = ServiceRequest::with([
            'customer',
            'category',
            'subCategory',
            'service',
            'photos',
            'targetProvider.providerProfile',
            'providerJobRequests.provider',
        ])->findOrFail($id);

        // Load lifecycle relationships separately
        $bids = ProviderBid::with(['provider.providerProfile', 'offers'])->where('service_request_id', $id)->get();
        $assignment = JobAssignment::with(['provider.providerProfile', 'technician.technicianProfile'])->where('service_request_id', $id)->latest()->first();
        $inspection = JobInspection::where('service_request_id', $id)->first();
        $quote = Quote::with('items')->where('service_request_id', $id)->first();
        $approvals = CustomerApproval::where('service_request_id', $id)->get();
        $progress = JobProgress::where('service_request_id', $id)->orderBy('created_at', 'asc')->get();
        $invoice = Invoice::with(['items', 'payments'])->where('service_request_id', $id)->first();
        $review = Review::where('service_request_id', $id)->first();
        $history = JobStatusHistory::with('changedBy')->where('service_request_id', $id)->latest()->get();

        $providers = User::where(function ($q) {
            $q->where('role', 'provider')
                ->orWhereHas('providerProfile');
        })->orderBy('name')->get();

        return view('admin.requests.show', compact(
            'serviceRequest',
            'bids',
            'assignment',
            'inspection',
            'quote',
            'approvals',
            'progress',
            'invoice',
            'review',
            'history',
            'providers'
        ));
    }

    /**
     * Download PDF invoice for a customer service request from admin panel.
     */
    public function downloadInvoice(Request $request, int $id)
    {
        $serviceRequest = ServiceRequest::with([
            'category',
            'subCategory',
            'service',
            'customer',
            'targetProvider.providerProfile',
            'jobAssignments.technician',
            'quotes.items',
            'invoices.items',
        ])->findOrFail($id);

        $customer = $serviceRequest->customer;
        $provider = $serviceRequest->targetProvider ?? $serviceRequest->jobAssignments->first()?->provider;
        $technician = $serviceRequest->jobAssignments->first()?->technician;
        $quote = $serviceRequest->quotes()->latest()->first();
        $invoice = $serviceRequest->invoices()->latest()->first();

        $invoiceNumber = $invoice?->invoice_number ?? 'INV-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT);
        $requestNumber = $serviceRequest->request_number ?? 'SR-'.$serviceRequest->id;

        $items = [];
        $subtotal = 0.0;
        $tax = 0.0;
        $discount = 0.0;
        $total = 0.0;

        if ($invoice && $invoice->items->isNotEmpty()) {
            foreach ($invoice->items as $invItem) {
                $amt = (float) $invItem->amount;
                $items[] = [
                    'description' => $invItem->description,
                    'type' => 'Service',
                    'unit_price' => $amt,
                    'total_price' => $amt,
                ];
            }
            $subtotal = (float) $invoice->subtotal;
            $tax = (float) $invoice->tax;
            $discount = (float) $invoice->discount;
            $total = (float) $invoice->total;
        } elseif ($quote && $quote->items->isNotEmpty()) {
            foreach ($quote->items as $qItem) {
                $items[] = [
                    'description' => $qItem->description,
                    'type' => $qItem->type ?? 'Service',
                    'unit_price' => (float) $qItem->unit_price,
                    'total_price' => (float) $qItem->total_price,
                ];
            }
            $subtotal = (float) $quote->labor_subtotal + (float) $quote->material_subtotal;
            $tax = (float) $quote->tax;
            $discount = (float) $quote->discount;
            $total = (float) $quote->total;
        } else {
            $basePrice = (float) ($serviceRequest->service?->base_price ?? 250.00);
            $items[] = [
                'description' => $serviceRequest->service?->name ?? $serviceRequest->description ?? 'Service Request Standard Package',
                'type' => 'Service',
                'unit_price' => $basePrice,
                'total_price' => $basePrice,
            ];
            $subtotal = $basePrice;
            $tax = round($subtotal * 0.05, 2);
            $total = $subtotal + $tax;
        }

        $tipAmount = (float) ($invoice?->tip_amount ?? 0);
        if ($invoice && (float) $invoice->total > 0) {
            $total = (float) $invoice->total;
        } else {
            $total = round($subtotal + $tax - $discount + $tipAmount, 2);
        }
        $depositPaid = min($serviceRequest->depositPaidAmount(), $total);

        $pdfData = [
            'invoiceNumber' => $invoiceNumber,
            'requestNumber' => $requestNumber,
            'dateIssued' => BusinessTime::now($serviceRequest->localTimezone())->format('M d, Y'),
            'customerName' => $customer?->name ?? 'Valued Customer',
            'customerEmail' => $customer?->email ?? 'customer@example.com',
            'customerPhone' => $customer?->phone ?? 'N/A',
            'serviceAddress' => $serviceRequest->address_line ?? 'Customer Address',
            'providerName' => $provider?->name ?? 'Grab A Hand Provider',
            'technicianName' => $technician?->name ?? 'Assigned Technician',
            'serviceTitle' => $serviceRequest->service?->name ?? $serviceRequest->description ?? 'On-Demand Service',
            'categoryName' => $serviceRequest->category?->name ?? 'General Maintenance',
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'tipAmount' => $tipAmount,
            'total' => $total,
            'depositPaid' => $depositPaid,
        ];

        $pdf = Pdf::loadView('invoices.pdf', $pdfData);
        $filename = "Invoice-{$requestNumber}.pdf";

        if ($request->boolean('inline') || $request->get('disposition') === 'inline') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Manually assign a provider to a service request.
     */
    public function assignProvider(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'provider_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $serviceRequest = ServiceRequest::findOrFail($id);

        $provider = User::where('id', $validated['provider_id'])
            ->where(function ($q) {
                $q->where('role', 'provider')
                    ->orWhereHas('providerProfile');
            })
            ->firstOrFail();

        $oldStatus = $serviceRequest->status->value;

        // Update target provider & status
        $serviceRequest->update([
            'target_provider_id' => $provider->id,
            'status' => ServiceRequestStatus::Awarded,
        ]);

        // Create or update ProviderJobRequest record for the assigned provider
        $providerProfile = $provider->providerProfile;
        ProviderJobRequest::updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $provider->id,
            ],
            [
                'provider_profile_id' => $providerProfile?->id,
                'status' => ProviderJobRequestStatus::Selected,
                'sent_at' => now(),
                'responded_at' => now(),
            ]
        );

        // Audit Trail Record
        JobStatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'changed_by_type' => 'admin',
            'changed_by_id' => $request->user()->id,
            'old_status' => $oldStatus,
            'new_status' => ServiceRequestStatus::Awarded->value,
            'reason' => "ADMIN MANUAL ASSIGNMENT: Assigned to Provider {$provider->name} (ID: {$provider->id})",
        ]);

        return back()->with('success', "Service request manually assigned to provider {$provider->name} successfully.");
    }

    /**
     * Controlled Admin Status Override with mandatory audit reason.
     */
    public function overrideStatus(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'new_status' => ['required', 'string', 'in:submitted,matching,invited,in_negotiation,awarded,assigned,in_progress,completed,cancelled'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $serviceRequest = ServiceRequest::findOrFail($id);
        $oldStatus = $serviceRequest->status->value;

        $serviceRequest->update(['status' => ServiceRequestStatus::from($validated['new_status'])]);

        // Mandatory Section 79 Audit Record
        JobStatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'changed_by_type' => 'admin',
            'changed_by_id' => $request->user()->id,
            'old_status' => $oldStatus,
            'new_status' => $validated['new_status'],
            'reason' => 'ADMIN OVERRIDE: '.$validated['reason'],
        ]);

        if ($validated['new_status'] === 'cancelled') {
            app(EmailNotificationService::class)->sendBookingCancelled(
                $serviceRequest,
                'admin',
                $validated['reason']
            );
        }

        return back()->with('success', "Request status overridden to {$validated['new_status']} with audit record.");
    }
}
