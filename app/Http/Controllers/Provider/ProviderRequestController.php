<?php

namespace App\Http\Controllers\Provider;

use App\Enums\JobAssignmentStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\ProviderBid;
use App\Models\ProviderJobRequest;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Services\Bidding\BiddingService;
use App\Services\Quote\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderRequestController extends Controller
{
    /**
     * Display incoming job invitations for provider.
     */
    public function incoming(Request $request): View
    {
        $providerId = $request->user()->id;

        $query = ProviderJobRequest::with(['serviceRequest.category', 'serviceRequest.subCategory', 'serviceRequest.service', 'serviceRequest.customer'])
            ->where('provider_id', $providerId)
            ->whereIn('status', ['sent', 'viewed'])
            ->whereDoesntHave('serviceRequest.jobAssignments', function ($q) use ($providerId) {
                $q->where('provider_id', $providerId);
            });

        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('serviceRequest', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('request_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address_line', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($catQ) use ($search) {
                            $catQ->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('service', function ($servQ) use ($search) {
                            $servQ->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        $jobRequests = $query->latest()->paginate(15)->withQueryString();
        $tab = 'incoming';

        return view('provider.requests.index', compact('jobRequests', 'tab'));
    }

    /**
     * Display accepted and active jobs for provider.
     */
    public function accepted(Request $request): View
    {
        $providerId = $request->user()->id;

        $query = ProviderJobRequest::with(['serviceRequest.category', 'serviceRequest.subCategory', 'serviceRequest.service', 'serviceRequest.customer'])
            ->where('provider_id', $providerId)
            ->where(function ($q) use ($providerId) {
                $q->whereIn('status', ['selected', 'accepted', 'bid_submitted'])
                    ->orWhereHas('serviceRequest.jobAssignments', function ($aQ) use ($providerId) {
                        $aQ->where('provider_id', $providerId);
                    });
            });

        if ($request->filled('status')) {
            $status = $request->get('status');
            if (in_array($status, ['selected', 'accepted'])) {
                $query->whereHas('serviceRequest', function ($q) {
                    $q->whereIn('status', ['awarded', 'selected', 'invited', 'in_negotiation']);
                });
            } else {
                $query->whereHas('serviceRequest', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }
        }

        if ($search = trim($request->get('search', ''))) {
            $query->whereHas('serviceRequest', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('request_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('address_line', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('category', function ($catQ) use ($search) {
                            $catQ->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('service', function ($servQ) use ($search) {
                            $servQ->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        $jobRequests = $query->latest()->paginate(15)->withQueryString();
        $tab = 'accepted';

        return view('provider.requests.index', compact('jobRequests', 'tab'));
    }

    /**
     * Index route rendering incoming or accepted.
     */
    public function index(Request $request): View
    {
        if ($request->get('tab') === 'accepted') {
            return $this->accepted($request);
        }

        return $this->incoming($request);
    }

    /**
     * Display single job request details strictly for the authenticated provider.
     */
    public function show(Request $request, int $id): View
    {
        $providerId = $request->user()->id;

        // Find provider job request strictly scoped to authenticated provider
        $jobRequest = ProviderJobRequest::with([
            'serviceRequest.category',
            'serviceRequest.subCategory',
            'serviceRequest.service',
            'serviceRequest.customer',
            'serviceRequest.photos',
        ])
            ->where('provider_id', $providerId)
            ->findOrFail($id);

        $serviceRequest = $jobRequest->serviceRequest;

        // Load bid and job assignment specific to this provider
        $myBid = ProviderBid::with('offers')
            ->where('service_request_id', $serviceRequest->id)
            ->where('provider_id', $providerId)
            ->first();

        $assignment = JobAssignment::with('technician')
            ->where('service_request_id', $serviceRequest->id)
            ->where('provider_id', $providerId)
            ->first();

        $providerProfile = $request->user()->providerProfile;
        $technicians = $providerProfile
            ? $providerProfile->technicians()->with('user')->where('is_active', true)->where('is_approved', true)->get()
            : collect();

        $quote = Quote::with(['items', 'technician'])
            ->where('service_request_id', $serviceRequest->id)
            ->where('provider_id', $providerId)
            ->first();

        return view('provider.requests.show', compact('jobRequest', 'serviceRequest', 'myBid', 'assignment', 'technicians', 'quote'));
    }

    /**
     * Submit a bid for a job request from provider panel.
     */
    public function submitBid(Request $request, int $id, BiddingService $biddingService): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'estimated_duration' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $jobRequest = ProviderJobRequest::where('provider_id', $request->user()->id)->findOrFail($id);

        $biddingService->submitBid($request->user(), $jobRequest->serviceRequest, $validated);

        return back()->with('success', 'Bid submitted successfully.');
    }

    /**
     * Assign technician to awarded request from provider panel.
     */
    public function assignTechnician(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'technician_id' => ['required', 'exists:users,id'],
        ]);

        $jobRequest = ProviderJobRequest::where('provider_id', $request->user()->id)->findOrFail($id);
        $serviceRequest = $jobRequest->serviceRequest;

        $providerProfile = $request->user()->providerProfile;
        $techProfile = $providerProfile
            ? $providerProfile->technicians()
                ->where('user_id', $validated['technician_id'])
                ->where('is_active', true)
                ->where('is_approved', true)
                ->first()
            : null;

        if (! $techProfile) {
            return back()->with('error', 'Selected technician is inactive or does not belong to your organization.');
        }

        JobAssignment::updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'provider_id' => $request->user()->id,
            ],
            [
                'technician_id' => $validated['technician_id'],
                'assigned_at' => now(),
                'status' => JobAssignmentStatus::Assigned,
            ]
        );

        $serviceRequest->update(['status' => ServiceRequestStatus::Assigned]);
        $jobRequest->update(['status' => ProviderJobRequestStatus::Selected]);

        return back()->with('success', 'Technician assigned successfully.');
    }

    /**
     * Accept a job request directly from provider panel.
     */
    public function acceptRequest(Request $request, int $id): RedirectResponse
    {
        $jobRequest = ProviderJobRequest::where('provider_id', $request->user()->id)->findOrFail($id);
        $serviceRequest = $jobRequest->serviceRequest;

        $jobRequest->update(['status' => ProviderJobRequestStatus::Selected]);
        $serviceRequest->update(['status' => ServiceRequestStatus::Awarded]);

        return back()->with('success', 'Job request accepted successfully! You can now assign a technician.');
    }

    /**
     * Approve technician-submitted quotation from provider panel.
     */
    public function approveQuote(Request $request, int $quoteId, QuoteService $quoteService): RedirectResponse
    {
        $quote = Quote::where('provider_id', $request->user()->id)->findOrFail($quoteId);
        $quoteService->approveByProvider($request->user(), $quote);

        return back()->with('success', 'Quotation approved successfully! It is now visible to the customer.');
    }

    /**
     * Provider updates technician-submitted quotation details.
     */
    public function updateQuote(Request $request, int $quoteId, QuoteService $quoteService): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'string', 'in:labor,material,additional_service'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'approve' => ['nullable', 'boolean'],
        ]);

        $quote = Quote::where('provider_id', $request->user()->id)->findOrFail($quoteId);
        $shouldApprove = $request->boolean('approve');

        $quoteService->updateQuoteByProvider(
            provider: $request->user(),
            quote: $quote,
            items: $request->items,
            taxRate: (float) ($request->tax_rate ?? 0.05),
            discount: (float) ($request->discount ?? 0.00),
            notes: $request->notes,
            approve: $shouldApprove
        );

        $msg = $shouldApprove
            ? 'Quotation updated and approved successfully! Sent to customer.'
            : 'Quotation updated successfully.';

        return back()->with('success', $msg);
    }

    /**
     * Get live location data of assigned technician for provider map view.
     */
    public function technicianLocation(Request $request, int $id): JsonResponse
    {
        $providerId = $request->user()->id;

        $jobRequest = ProviderJobRequest::where('provider_id', $providerId)->findOrFail($id);
        $serviceRequest = $jobRequest->serviceRequest;

        $assignment = JobAssignment::with(['technician.technicianProfile'])
            ->where('service_request_id', $serviceRequest->id)
            ->where('provider_id', $providerId)
            ->first();

        if (! $assignment || ! $assignment->technician) {
            return response()->json([
                'success' => false,
                'assigned' => false,
                'message' => 'No technician assigned yet.',
            ]);
        }

        $techUser = $assignment->technician;
        $profile = $techUser->technicianProfile;

        // Destination coordinates from service request
        $destLat = (float) ($serviceRequest->latitude ?? 12.9716);
        $destLng = (float) ($serviceRequest->longitude ?? 77.5946);

        // Tech live coordinates, or fallback nearby initial location if not set yet
        $techLat = $profile?->current_latitude !== null
            ? (float) $profile->current_latitude
            : ($destLat - 0.012);

        $techLng = $profile?->current_longitude !== null
            ? (float) $profile->current_longitude
            : ($destLng - 0.009);

        $lastUpdated = $profile?->last_location_at
            ? $profile->last_location_at->diffForHumans()
            : 'Just now';

        $statusVal = is_object($assignment->status) ? $assignment->status->value : (string) $assignment->status;

        return response()->json([
            'success' => true,
            'assigned' => true,
            'technician_id' => $techUser->id,
            'technician_name' => $techUser->name,
            'technician_email' => $techUser->email,
            'technician_phone' => $techUser->phone ?? 'N/A',
            'duty_status' => $profile?->duty_status ?? 'online',
            'assignment_status' => ucfirst(str_replace('_', ' ', $statusVal)),
            'latitude' => $techLat,
            'longitude' => $techLng,
            'destination_latitude' => $destLat,
            'destination_longitude' => $destLng,
            'destination_address' => $serviceRequest->address_line ?? 'Service Location',
            'last_updated' => $lastUpdated,
        ]);
    }

    /**
     * Download PDF invoice for a completed/assigned service request.
     */
    public function downloadInvoice(Request $request, int $id)
    {
        $providerId = $request->user()->id;

        $jobRequest = ProviderJobRequest::where('provider_id', $providerId)->find($id);
        if (! $jobRequest) {
            $serviceRequest = ServiceRequest::findOrFail($id);
        } else {
            $serviceRequest = $jobRequest->serviceRequest;
        }

        $serviceRequest->load([
            'category',
            'subCategory',
            'service',
            'customer',
            'targetProvider',
            'jobAssignments.technician',
            'quotes.items',
            'invoices.items',
        ]);

        $customer = $serviceRequest->customer;
        $provider = $serviceRequest->targetProvider ?? $request->user();
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

        $depositPaid = min(250.00, $total > 0 ? $total : 250.00);

        $pdfData = [
            'invoiceNumber' => $invoiceNumber,
            'requestNumber' => $requestNumber,
            'dateIssued' => now()->format('M d, Y'),
            'customerName' => $customer?->name ?? 'Valued Customer',
            'customerEmail' => $customer?->email ?? 'customer@example.com',
            'customerPhone' => $customer?->phone ?? 'N/A',
            'serviceAddress' => $serviceRequest->address_line ?? 'Customer Address',
            'providerName' => $provider?->name ?? 'On Demand Provider',
            'technicianName' => $technician?->name ?? 'Assigned Technician',
            'serviceTitle' => $serviceRequest->service?->name ?? $serviceRequest->description ?? 'On-Demand Service',
            'categoryName' => $serviceRequest->category?->name ?? 'General Maintenance',
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
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
}
