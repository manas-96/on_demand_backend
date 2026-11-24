<?php

namespace App\Http\Controllers\Provider;

use App\Enums\JobAssignmentStatus;
use App\Enums\NotificationType;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\ProviderBid;
use App\Models\ProviderJobRequest;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Bidding\BiddingService;
use App\Services\BusinessTime;
use App\Services\EmailNotificationService;
use App\Services\PushNotificationService;
use App\Services\Quote\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $query = ProviderJobRequest::with([
            'serviceRequest.category',
            'serviceRequest.subCategory',
            'serviceRequest.service',
            'serviceRequest.customer',
            'serviceRequest.quotes.items',
            'serviceRequest.jobAssignments.technician',
        ])
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
            ->latest()
            ->first();

        $inspections = JobInspection::with('technician')
            ->where('service_request_id', $serviceRequest->id)
            ->latest()
            ->get();

        $progressUpdates = JobProgress::with('technician')
            ->where('service_request_id', $serviceRequest->id)
            ->latest()
            ->get();

        return view('provider.requests.show', compact('jobRequest', 'serviceRequest', 'myBid', 'assignment', 'technicians', 'quote', 'inspections', 'progressUpdates'));
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

        if (! $serviceRequest->isTechnicianAssignmentAllowed()) {
            $formattedDate = $serviceRequest->preferred_date ? $serviceRequest->preferred_date->format('M d, Y') : 'the scheduled date';

            return back()->with('error', "Technician assignment is locked until the scheduled booking date ({$formattedDate}).");
        }

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

        $technicianId = $validated['technician_id'];

        // Customer Notification
        app(PushNotificationService::class)->send(
            userId: $serviceRequest->customer_id,
            title: 'Technician Assigned',
            body: 'A technician has been assigned to your service request.',
            type: NotificationType::Booking->value,
            data: [
                'type' => 'booking',
                'booking_id' => $serviceRequest->request_number,
                'screen' => 'booking_details',
            ],
            eventId: $serviceRequest->request_number.'_tech_assigned_cust_'.$technicianId.'_'.now()->timestamp
        );

        // Technician Notification
        app(PushNotificationService::class)->send(
            userId: $technicianId,
            title: 'New Job Assignment',
            body: 'You have been assigned to a new service request.',
            type: NotificationType::Booking->value,
            data: [
                'type' => 'booking',
                'booking_id' => $serviceRequest->request_number,
                'screen' => 'booking_details',
            ],
            eventId: $serviceRequest->request_number.'_tech_assigned_tech_'.$technicianId.'_'.now()->timestamp
        );

        $techUser = $techProfile?->user ?? User::find($technicianId);

        app(EmailNotificationService::class)->sendRequestAcceptedOrAssignedToCustomer(
            $serviceRequest,
            $request->user(),
            $techUser
        );

        app(EmailNotificationService::class)->sendTechnicianAssignedToTechnician(
            $serviceRequest,
            $techUser,
            $request->user()
        );

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

        app(PushNotificationService::class)->send(
            userId: $serviceRequest->customer_id,
            title: 'Job Accepted',
            body: 'Your service request has been accepted by the provider.',
            type: NotificationType::Booking->value,
            data: [
                'type' => 'booking',
                'booking_id' => $serviceRequest->request_number,
                'screen' => 'booking_details',
            ],
            eventId: $serviceRequest->request_number.'_accepted_'.$serviceRequest->customer_id.'_'.now()->timestamp
        );

        app(EmailNotificationService::class)->sendRequestAcceptedOrAssignedToCustomer(
            $serviceRequest,
            $request->user()
        );

        return back()->with('success', 'Job request accepted successfully! You can now assign a technician.');
    }

    /**
     * Approve technician-submitted quotation from provider panel.
     */
    public function approveQuote(Request $request, int $quoteId, QuoteService $quoteService): RedirectResponse
    {
        $quote = Quote::where(function ($q) use ($request) {
            $q->where('provider_id', $request->user()->id)
                ->orWhereHas('serviceRequest.jobAssignments', function ($aq) use ($request) {
                    $aq->where('provider_id', $request->user()->id);
                });
        })->findOrFail($quoteId);

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

        $quote = Quote::where(function ($q) use ($request) {
            $q->where('provider_id', $request->user()->id)
                ->orWhereHas('serviceRequest.jobAssignments', function ($aq) use ($request) {
                    $aq->where('provider_id', $request->user()->id);
                });
        })->findOrFail($quoteId);
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
     * Trigger push notification to customer for chat message sent from provider panel.
     */
    public function notifyChat(Request $request, int $id, PushNotificationService $notificationService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $jobRequest = ProviderJobRequest::with('serviceRequest.customer')
            ->where('provider_id', $request->user()->id)
            ->findOrFail($id);

        $serviceRequest = $jobRequest->serviceRequest;
        $customer = $serviceRequest->customer;

        if ($customer) {
            $notificationService->send(
                userId: (string) $customer->id,
                title: $request->user()->name ?? 'Provider Message',
                body: $validated['message'],
                type: NotificationType::Message->value,
                data: [
                    'type' => NotificationType::Message->value,
                    'booking_id' => $serviceRequest->request_number,
                    'job_id' => $serviceRequest->request_number,
                    'sender_id' => (string) $request->user()->id,
                    'receiver_id' => (string) $customer->id,
                    'screen' => 'chat',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat push notification sent to customer.',
        ]);
    }

    /**
     * Send chat message from provider panel (writes to Firestore via backend & dispatches push notification).
     */
    public function sendChat(Request $request, int $id, PushNotificationService $notificationService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $providerId = $request->user()->id;

        // Support lookup by either ProviderJobRequest ID or ServiceRequest ID
        $jobRequest = ProviderJobRequest::with('serviceRequest.customer')
            ->where('provider_id', $providerId)
            ->find($id);

        if (! $jobRequest) {
            $serviceRequest = ServiceRequest::with('customer')
                ->where('id', $id)
                ->where(function ($q) use ($providerId) {
                    $q->where('target_provider_id', $providerId)
                        ->orWhereHas('jobAssignments', fn ($aq) => $aq->where('provider_id', $providerId))
                        ->orWhereHas('providerJobRequests', fn ($pq) => $pq->where('provider_id', $providerId));
                })
                ->firstOrFail();
        } else {
            $serviceRequest = $jobRequest->serviceRequest;
        }

        $customer = $serviceRequest->customer;
        $customerId = $customer?->id ?? $serviceRequest->customer_id;
        $provider = $request->user();

        if (! $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Customer for this service request could not be identified.',
            ], 422);
        }

        $firestoreWritten = $notificationService->sendFirestoreMessage(
            jobId: $serviceRequest->request_number,
            senderId: (string) $provider->id,
            receiverId: (string) $customerId,
            message: $validated['message']
        );

        if (! $firestoreWritten) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to persist message to chat server. Please retry.',
            ], 500);
        }

        // Send FCM push notification (non-blocking / error-tolerant)
        try {
            $notificationService->send(
                userId: (string) $customerId,
                title: $provider->name ?? 'Provider Message',
                body: $validated['message'],
                type: NotificationType::Message->value,
                data: [
                    'type' => NotificationType::Message->value,
                    'booking_id' => $serviceRequest->request_number,
                    'job_id' => $serviceRequest->request_number,
                    'sender_id' => (string) $provider->id,
                    'receiver_id' => (string) $customerId,
                    'screen' => 'chat',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('sendChat: Push notification dispatch notice', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chat message sent successfully.',
        ]);
    }
}
