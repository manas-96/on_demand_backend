<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\JobAssignmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentType;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateServiceRequestForm;
use App\Http\Resources\ProviderBidResource;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Invoice;
use App\Models\JobStatusHistory;
use App\Models\ProviderBid;
use App\Models\Quote;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Services\Bidding\BiddingService;
use App\Services\BusinessTime;
use App\Services\EmailNotificationService;
use App\Services\Matching\ProviderMatchingService;
use App\Services\Media\ImageUploadService;
use App\Services\Payment\BookingPaymentService;
use App\Services\PushNotificationService;
use App\Services\Quote\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CustomerRequestController extends Controller
{
    public function __construct(
        protected ProviderMatchingService $matchingService,
        protected BiddingService $biddingService,
        protected QuoteService $quoteService,
        protected BookingPaymentService $bookingPaymentService,
        protected ImageUploadService $uploadService,
    ) {}

    /**
     * List customer's own service requests with optional status filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ServiceRequest::with(['category', 'subCategory', 'service', 'photos', 'jobAssignments.technician', 'jobAssignments.provider', 'targetProvider', 'quotes.provider'])
            ->where('customer_id', $request->user()->id);

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'active') {
                $query->whereIn('status', [
                    ServiceRequestStatus::Submitted,
                    ServiceRequestStatus::Matching,
                    ServiceRequestStatus::Invited,
                    ServiceRequestStatus::InNegotiation,
                    ServiceRequestStatus::Awarded,
                    ServiceRequestStatus::Assigned,
                    ServiceRequestStatus::InProgress,
                ]);
            } else {
                $query->where('status', $status);
            }
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Service requests retrieved successfully.',
            'data' => ServiceRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    /**
     * Store new customer service request.
     */
    public function store(CreateServiceRequestForm $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['customer_id'] = $request->user()->id;
        $validated['request_number'] = ServiceRequest::generateRequestNumber();
        $validated['status'] = ServiceRequestStatus::PendingPayment;
        // Customer's zone: explicit field, else the app's X-Timezone header, else the business zone.
        $validated['timezone'] = BusinessTime::timezone($validated['timezone'] ?? null);

        $serviceRequest = ServiceRequest::create($validated);

        // Deposit is decided server-side; providers are only invited once it is paid.
        $serviceRequest->update(['deposit_amount' => $this->bookingPaymentService->depositAmountFor($serviceRequest)]);

        // Process uploaded file binaries or photo URL strings
        if ($request->hasFile('photos')) {
            $uploadedPaths = $this->uploadService->uploadMultiple($request->file('photos'), 'requests', 'public');
            foreach ($uploadedPaths as $path) {
                $serviceRequest->photos()->create(['file_path' => $path]);
            }
        } elseif (! empty($validated['photos']) && is_array($validated['photos'])) {
            foreach ($validated['photos'] as $photoItem) {
                if (is_string($photoItem) && trim($photoItem) !== '') {
                    $str = trim($photoItem);
                    // Filter out local device file paths (e.g. /data/user/0/... or /var/...)
                    if (Str::startsWith($str, ['/data/', '/var/', 'data/', 'var/'])) {
                        continue;
                    }
                    $serviceRequest->photos()->create(['file_path' => $str]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Service request created. Pay the booking deposit to confirm it.',
            'data' => new ServiceRequestResource($serviceRequest->fresh()->load(['category', 'subCategory', 'service', 'photos'])),
        ], 201);
    }

    /**
     * Helper to find ServiceRequest for authenticated customer by ID or request_number.
     */
    private function findServiceRequestForUser(Request $request, string|int $id): ServiceRequest
    {
        $cleanId = trim((string) $id);
        $hyphenated = (strlen($cleanId) === 14 && is_numeric($cleanId))
            ? 'SR-'.substr($cleanId, 0, 8).'-'.substr($cleanId, 8)
            : $cleanId;

        return ServiceRequest::where('customer_id', $request->user()->id)
            ->where(function ($query) use ($cleanId, $hyphenated) {
                if (is_numeric($cleanId) && (float) $cleanId <= 2147483647) {
                    $query->where('id', (int) $cleanId);
                }
                $query->orWhere('request_number', $cleanId)
                    ->orWhere('request_number', $hyphenated)
                    ->orWhere('request_number', 'LIKE', '%'.$cleanId.'%')
                    ->orWhereRaw("REPLACE(REPLACE(request_number, '-', ''), 'SR', '') LIKE ?", ["%{$cleanId}%"]);
            })
            ->firstOrFail();
    }

    /**
     * Display customer's single service request details.
     */
    public function show(Request $request, string|int $id): JsonResponse
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);
        $serviceRequest->load(['category', 'subCategory', 'service', 'photos', 'targetProvider', 'jobAssignments.technician', 'jobAssignments.provider', 'quotes.provider', 'review']);

        return response()->json([
            'success' => true,
            'message' => 'Request details retrieved.',
            'data' => new ServiceRequestResource($serviceRequest),
        ]);
    }

    /**
     * View bids for customer request.
     */
    public function bids(Request $request, string|int $id): JsonResponse
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);

        $bids = ProviderBid::with(['provider', 'offers'])
            ->where('service_request_id', $serviceRequest->id)
            ->orderBy('amount', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Bids retrieved.',
            'data' => ProviderBidResource::collection($bids),
        ]);
    }

    /**
     * Negotiate counter offer on a bid.
     */
    public function negotiate(Request $request, int $bidId): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'message' => ['nullable', 'string'],
        ]);

        $bid = ProviderBid::with('serviceRequest')->findOrFail($bidId);

        if ($bid->serviceRequest->customer_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized resource access.'], 403);
        }

        $offer = $this->biddingService->counterOffer($request->user(), $bid, (float) $request->amount, $request->message);

        return response()->json([
            'success' => true,
            'message' => 'Counter offer submitted successfully.',
            'data' => $offer,
        ]);
    }

    /**
     * Select winning provider bid.
     */
    public function selectBid(Request $request, int $bidId): JsonResponse
    {
        $bid = ProviderBid::with('serviceRequest')->findOrFail($bidId);

        if ($bid->serviceRequest->customer_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized resource access.'], 403);
        }

        $this->biddingService->selectBid($bid->serviceRequest, $bid);

        return response()->json([
            'success' => true,
            'message' => 'Provider bid selected successfully.',
        ]);
    }

    /**
     * Retrieve quotation for service request (strictly requires provider approval).
     */
    public function getQuote(Request $request, string|int $id): JsonResponse
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);

        $quote = Quote::with('items')
            ->where('service_request_id', $serviceRequest->id)
            ->where('is_approved_by_provider', true)
            ->latest()
            ->first();

        if (! $quote) {
            return response()->json([
                'success' => false,
                'message' => 'No approved quotation available for this service request yet.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quotation retrieved successfully.',
            'data' => new QuoteResource($quote),
        ]);
    }

    /**
     * Approve post-inspection quote.
     */
    public function approveQuote(Request $request, string|int $quoteId): JsonResponse
    {
        $cleanId = trim((string) $quoteId);
        $hyphenated = (strlen($cleanId) === 14 && is_numeric($cleanId))
            ? 'SR-'.substr($cleanId, 0, 8).'-'.substr($cleanId, 8)
            : $cleanId;

        // The app sends the booking id or number here, so match either the quote or its request,
        // but only ever among this customer's own requests.
        $quote = Quote::with('serviceRequest')
            ->whereHas('serviceRequest', fn ($sq) => $sq->where('customer_id', $request->user()->id))
            ->where(function ($query) use ($cleanId, $hyphenated) {
                if (is_numeric($cleanId) && (float) $cleanId <= 2147483647) {
                    $query->where('id', (int) $cleanId)
                        ->orWhere('service_request_id', (int) $cleanId);
                }
                $query->orWhereHas('serviceRequest', function ($sq) use ($cleanId, $hyphenated) {
                    $sq->where('request_number', $cleanId)
                        ->orWhere('request_number', $hyphenated)
                        ->orWhere('request_number', 'LIKE', '%'.$cleanId.'%')
                        ->orWhereRaw("REPLACE(REPLACE(request_number, '-', ''), 'SR', '') LIKE ?", ["%{$cleanId}%"]);
                });
            })
            ->latest()
            ->first();

        if (! $quote) {
            return response()->json(['success' => false, 'message' => 'Quotation not found.'], 404);
        }

        // Provider approval is the provider's step; a customer can only approve a quote the provider has signed off.
        try {
            $this->quoteService->approveQuote($request->user(), $quote);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commercial quote approved successfully.',
            'data' => new QuoteResource($quote->fresh()),
        ]);
    }

    /**
     * Start a Stripe payment for the booking deposit or the remaining balance (amounts computed server-side).
     */
    public function paymentIntent(Request $request, string|int $id): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::enum(PaymentType::class)],
            'tip_amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $serviceRequest = $this->findServiceRequestForUser($request, $id);

        $data = $validated['type'] === PaymentType::Deposit->value
            ? $this->bookingPaymentService->createDepositIntent($serviceRequest)
            : $this->bookingPaymentService->createBalanceIntent($serviceRequest, (float) ($validated['tip_amount'] ?? 0));

        return response()->json([
            'success' => true,
            'message' => ($data['requires_payment'] ?? true) ? 'Payment intent ready.' : 'Nothing left to pay.',
            'data' => $data,
        ]);
    }

    /**
     * Confirm a Stripe payment after the payment sheet completes (verified against Stripe, idempotent).
     */
    public function confirmPayment(Request $request, string|int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        $serviceRequest = $this->findServiceRequestForUser($request, $id);
        $payment = $this->bookingPaymentService->confirm($serviceRequest, $validated['payment_intent_id']);

        return response()->json([
            'success' => true,
            'message' => $payment->type === PaymentType::Deposit
                ? 'Deposit received. Your request has been sent to providers.'
                : 'Payment received. Thank you!',
            'data' => [
                'payment_id' => $payment->id,
                'payment_type' => $payment->type->value,
                'amount' => (float) $payment->amount,
                'summary' => $this->bookingPaymentService->summary($serviceRequest->fresh()),
            ],
        ]);
    }

    /**
     * Deposit paid, invoice total and remaining balance for a request.
     */
    public function paymentSummary(Request $request, string|int $id): JsonResponse
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);

        return response()->json([
            'success' => true,
            'data' => $this->bookingPaymentService->summary($serviceRequest),
        ]);
    }

    /**
     * Download PDF invoice for a customer service request.
     */
    public function downloadInvoice(Request $request, string|int $id)
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);
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
        $provider = $serviceRequest->targetProvider;
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
     * Get review for a customer service request.
     */
    public function getReview(Request $request, string|int $id): JsonResponse
    {
        $serviceRequest = $this->findServiceRequestForUser($request, $id);
        $serviceRequest->load(['review.technician', 'review.provider']);

        $review = $serviceRequest->review;

        return response()->json([
            'success' => true,
            'message' => $review ? 'Review retrieved.' : 'No review found.',
            'data' => $review ? [
                'id' => $review->id,
                'service_request_id' => $review->service_request_id,
                'customer_id' => $review->customer_id,
                'provider_id' => $review->provider_id,
                'technician_id' => $review->technician_id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'updated_at' => $review->updated_at?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Submit or update review for completed service request.
     */
    public function storeReview(Request $request, string|int $id): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $serviceRequest = $this->findServiceRequestForUser($request, $id);
        $serviceRequest->load('jobAssignments.technician');

        // Verify that service request is completed
        $statusVal = is_string($serviceRequest->status)
            ? $serviceRequest->status
            : ($serviceRequest->status?->value ?? '');

        if (strtolower($statusVal) !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Reviews can only be submitted for completed service requests.',
            ], 422);
        }

        $providerId = $serviceRequest->target_provider_id
            ?? $serviceRequest->jobAssignments->first()?->provider_id
            ?? $request->user()->id;

        $technicianId = $serviceRequest->jobAssignments->first()?->technician_id;

        $review = Review::updateOrCreate(
            [
                'service_request_id' => $serviceRequest->id,
                'customer_id' => $request->user()->id,
            ],
            [
                'provider_id' => $providerId,
                'technician_id' => $technicianId,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data' => [
                'id' => $review->id,
                'service_request_id' => $review->service_request_id,
                'customer_id' => $review->customer_id,
                'provider_id' => $review->provider_id,
                'technician_id' => $review->technician_id,
                'rating' => (int) $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at?->toIso8601String(),
                'updated_at' => $review->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Cancel an active service request by the owning customer.
     */
    public function cancel(Request $request, string|int $id): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $cleanId = trim((string) $id);
        $hyphenated = (strlen($cleanId) === 14 && is_numeric($cleanId))
            ? 'SR-'.substr($cleanId, 0, 8).'-'.substr($cleanId, 8)
            : $cleanId;

        $serviceRequest = ServiceRequest::with(['customer', 'category', 'targetProvider', 'jobAssignments.technician', 'jobAssignments.provider'])
            ->where(function ($query) use ($cleanId, $hyphenated) {
                if (is_numeric($cleanId) && (float) $cleanId <= 2147483647) {
                    $query->where('id', (int) $cleanId);
                }
                $query->orWhere('request_number', $cleanId)
                    ->orWhere('request_number', $hyphenated)
                    ->orWhere('request_number', 'LIKE', '%'.$cleanId.'%')
                    ->orWhereRaw("REPLACE(REPLACE(request_number, '-', ''), 'SR', '') LIKE ?", ["%{$cleanId}%"]);
            })
            ->first();

        if (! $serviceRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Service request not found.',
            ], 404);
        }

        if ($serviceRequest->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You do not own this service request.',
            ], 403);
        }

        if ($serviceRequest->status === ServiceRequestStatus::Cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Service request is already cancelled.',
            ], 422);
        }

        if ($serviceRequest->status === ServiceRequestStatus::Completed) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a completed service request.',
            ], 422);
        }

        if ($serviceRequest->status === ServiceRequestStatus::InProgress) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a service request that is already in progress. Please contact support.',
            ], 422);
        }

        $oldStatus = $serviceRequest->status->value;

        // Transition status to Cancelled
        $serviceRequest->update(['status' => ServiceRequestStatus::Cancelled]);

        // Decline any active/pending job assignments
        $activeAssignment = $serviceRequest->jobAssignments
            ->whereNotIn('status', [JobAssignmentStatus::Declined, JobAssignmentStatus::Completed])
            ->first();

        if ($activeAssignment) {
            $activeAssignment->update(['status' => JobAssignmentStatus::Declined]);
        }

        // Record audit trail history
        JobStatusHistory::create([
            'service_request_id' => $serviceRequest->id,
            'job_assignment_id' => $activeAssignment?->id,
            'changed_by_type' => 'customer',
            'changed_by_id' => $request->user()->id,
            'old_status' => $oldStatus,
            'new_status' => ServiceRequestStatus::Cancelled->value,
            'reason' => $request->input('reason', 'Cancelled by customer'),
        ]);

        // Dispatch push notification to assigned provider if applicable
        $targetProviderId = $serviceRequest->target_provider_id ?? $activeAssignment?->provider_id;
        if ($targetProviderId) {
            app(PushNotificationService::class)->send(
                userId: $targetProviderId,
                title: 'Booking Cancelled',
                body: "Booking #{$serviceRequest->request_number} was cancelled by the customer.",
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => (string) $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_cancelled_provider_'.now()->timestamp
            );
        }

        // Dispatch push notification to assigned technician if applicable
        $technicianId = $activeAssignment?->technician_id;
        if ($technicianId) {
            app(PushNotificationService::class)->send(
                userId: $technicianId,
                title: 'Booking Cancelled',
                body: "Booking #{$serviceRequest->request_number} was cancelled by the customer.",
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => (string) $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_cancelled_tech_'.now()->timestamp
            );
        }

        // Dispatch email notification across all stakeholders (customer, provider, technician)
        app(EmailNotificationService::class)->sendBookingCancelled(
            $serviceRequest,
            'customer',
            $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Service request cancelled successfully.',
            'data' => new ServiceRequestResource($serviceRequest->fresh()->load(['category', 'subCategory', 'service', 'photos', 'jobAssignments.technician', 'jobAssignments.provider'])),
        ]);
    }
}
