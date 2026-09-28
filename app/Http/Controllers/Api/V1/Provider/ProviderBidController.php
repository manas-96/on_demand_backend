<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Enums\JobAssignmentStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\AssignTechnicianRequest;
use App\Http\Requests\Provider\SubmitBidRequest;
use App\Http\Resources\ProviderBidResource;
use App\Http\Resources\QuoteResource;
use App\Models\JobAssignment;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Services\Bidding\BiddingService;
use App\Services\Quote\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProviderBidController extends Controller
{
    public function __construct(protected BiddingService $biddingService) {}

    /**
     * Get incoming requests dispatched to this provider.
     */
    public function incomingRequests(Request $request): JsonResponse
    {
        $provider = $request->user();
        if (! $provider->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $invitations = ProviderJobRequest::with(['serviceRequest.category', 'serviceRequest.service', 'serviceRequest.customer'])
            ->where('provider_id', $provider->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Incoming job requests retrieved.',
            'data' => $invitations,
        ]);
    }

    /**
     * Submit marketplace bid for a request.
     */
    public function submitBid(SubmitBidRequest $request, int $requestId): JsonResponse
    {
        $serviceRequest = ServiceRequest::findOrFail($requestId);

        $bid = $this->biddingService->submitBid(
            $request->user(),
            $serviceRequest,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Bid submitted successfully.',
            'data' => new ProviderBidResource($bid),
        ], 201);
    }

    /**
     * Assign employed technician to awarded request.
     */
    public function assignTechnician(AssignTechnicianRequest $request, int $requestId): JsonResponse
    {
        $provider = $request->user();
        $serviceRequest = ServiceRequest::findOrFail($requestId);
        $technicianId = $request->technician_id;

        // Mandatory Rule 3: Validate technician belongs to this provider
        $techProfile = TechnicianProfile::where('user_id', $technicianId)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $techProfile) {
            throw new InvalidArgumentException('Technician does not belong to your organization.');
        }

        if (! $techProfile->is_active || ! $techProfile->is_approved) {
            throw new InvalidArgumentException('Selected technician is currently inactive or unapproved.');
        }

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $provider->id,
            'technician_id' => $technicianId,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $serviceRequest->update(['status' => ServiceRequestStatus::Assigned]);
        ProviderJobRequest::where('service_request_id', $serviceRequest->id)
            ->where('provider_id', $provider->id)
            ->update(['status' => ProviderJobRequestStatus::Selected]);

        return response()->json([
            'success' => true,
            'message' => 'Technician assigned successfully.',
            'data' => $assignment,
        ]);
    }

    /**
     * View provider earnings.
     */
    public function earnings(Request $request): JsonResponse
    {
        if (! $request->user()->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $earnings = ProviderEarning::with('serviceRequest')
            ->where('provider_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Earnings ledger retrieved.',
            'data' => $earnings,
        ]);
    }

    /**
     * Provider approves technician-submitted quotation.
     */
    public function approveQuote(Request $request, int $quoteId, QuoteService $quoteService): JsonResponse
    {
        $provider = $request->user();
        if (! $provider->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $quote = Quote::where('provider_id', $provider->id)->findOrFail($quoteId);
        $approvedQuote = $quoteService->approveByProvider($provider, $quote);

        return response()->json([
            'success' => true,
            'message' => 'Quotation approved successfully and sent to customer.',
            'data' => new QuoteResource($approvedQuote->load('items')),
        ]);
    }

    /**
     * Provider updates technician-submitted quotation details.
     */
    public function updateQuote(Request $request, int $quoteId, QuoteService $quoteService): JsonResponse
    {
        $provider = $request->user();
        if (! $provider->isProvider()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $validated = $request->validate([
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

        $quote = Quote::where('provider_id', $provider->id)->findOrFail($quoteId);

        $updatedQuote = $quoteService->updateQuoteByProvider(
            provider: $provider,
            quote: $quote,
            items: $validated['items'],
            taxRate: (float) ($validated['tax_rate'] ?? 0.05),
            discount: (float) ($validated['discount'] ?? 0.00),
            notes: $validated['notes'] ?? null,
            approve: (bool) ($validated['approve'] ?? false)
        );

        $msg = ($validated['approve'] ?? false)
            ? 'Quotation updated and approved successfully! Sent to customer.'
            : 'Quotation updated successfully.';

        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => new QuoteResource($updatedQuote),
        ]);
    }
}
