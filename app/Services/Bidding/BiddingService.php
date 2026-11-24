<?php

namespace App\Services\Bidding;

use App\Enums\BidOfferStatus;
use App\Enums\NotificationType;
use App\Enums\ProviderBidStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\BidOffer;
use App\Models\ProviderBid;
use App\Models\ProviderJobRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BiddingService
{
    /**
     * Submit an initial bid for a service request.
     */
    public function submitBid(User $provider, ServiceRequest $request, array $data): ProviderBid
    {
        if (! $provider->isProvider()) {
            throw new InvalidArgumentException('User is not authorized as a Service Provider.');
        }

        return DB::transaction(function () use ($provider, $request, $data) {
            $bid = ProviderBid::create([
                'service_request_id' => $request->id,
                'provider_id' => $provider->id,
                'amount' => $data['amount'],
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'availability_date' => $data['availability_date'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => ProviderBidStatus::Submitted,
                'expires_at' => now()->addDays(2),
            ]);

            // Record initial offer in negotiation history
            BidOffer::create([
                'provider_bid_id' => $bid->id,
                'sender_type' => 'provider',
                'sender_id' => $provider->id,
                'amount' => $data['amount'],
                'message' => $data['message'] ?? 'Initial Bid Submitted',
                'status' => BidOfferStatus::Pending,
            ]);

            // Update ProviderJobRequest status if exists
            ProviderJobRequest::where('service_request_id', $request->id)
                ->where('provider_id', $provider->id)
                ->update([
                    'status' => ProviderJobRequestStatus::BidSubmitted,
                    'responded_at' => now(),
                ]);

            if ($request->status === ServiceRequestStatus::Submitted || $request->status === ServiceRequestStatus::Matching) {
                $request->update(['status' => ServiceRequestStatus::Invited]);
            }

            return $bid;
        });
    }

    /**
     * Counter offer during bid negotiation without destroying audit history.
     */
    public function counterOffer(User $sender, ProviderBid $bid, float $amount, ?string $message = null): BidOffer
    {
        return DB::transaction(function () use ($sender, $bid, $amount, $message) {
            $senderType = $sender->isCustomer() ? 'customer' : 'provider';

            // Mark previous offers as countered
            BidOffer::where('provider_bid_id', $bid->id)
                ->where('status', BidOfferStatus::Pending)
                ->update(['status' => BidOfferStatus::Countered]);

            // Create new offer entry
            $offer = BidOffer::create([
                'provider_bid_id' => $bid->id,
                'sender_type' => $senderType,
                'sender_id' => $sender->id,
                'amount' => $amount,
                'message' => $message,
                'status' => BidOfferStatus::Pending,
            ]);

            // Update bid current amount and status
            $bid->update([
                'amount' => $amount,
                'status' => ProviderBidStatus::UnderNegotiation,
            ]);

            $bid->serviceRequest->update(['status' => ServiceRequestStatus::InNegotiation]);

            return $offer;
        });
    }

    /**
     * Select winning bid atomically with database pessimistic locking.
     */
    public function selectBid(ServiceRequest $request, ProviderBid $bid): bool
    {
        return DB::transaction(function () use ($request, $bid) {
            // Pessimistically lock service request row to prevent race conditions
            $lockedRequest = ServiceRequest::where('id', $request->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedRequest->status, [ServiceRequestStatus::Awarded, ServiceRequestStatus::Completed, ServiceRequestStatus::Cancelled])) {
                throw new InvalidArgumentException('This service request is no longer available for bid selection.');
            }

            if ($bid->service_request_id !== $lockedRequest->id) {
                throw new InvalidArgumentException('Selected bid does not belong to this service request.');
            }

            if ($bid->expires_at && $bid->expires_at->isPast()) {
                throw new InvalidArgumentException('Selected bid has expired.');
            }

            // Accept winning bid
            $bid->update(['status' => ProviderBidStatus::Accepted]);

            // Mark service request as Awarded
            $lockedRequest->update(['status' => ServiceRequestStatus::Awarded]);

            // Reject all competing bids for this service request
            ProviderBid::where('service_request_id', $lockedRequest->id)
                ->where('id', '!=', $bid->id)
                ->whereIn('status', [ProviderBidStatus::Submitted, ProviderBidStatus::UnderNegotiation])
                ->update(['status' => ProviderBidStatus::Rejected]);

            // Update ProviderJobRequest tracking
            ProviderJobRequest::where('service_request_id', $lockedRequest->id)
                ->where('provider_id', $bid->provider_id)
                ->update(['status' => ProviderJobRequestStatus::Selected]);

            ProviderJobRequest::where('service_request_id', $lockedRequest->id)
                ->where('provider_id', '!=', $bid->provider_id)
                ->update(['status' => ProviderJobRequestStatus::Rejected]);

            app(PushNotificationService::class)->send(
                userId: $lockedRequest->customer_id,
                title: 'Job Accepted',
                body: 'Your service request has been accepted by the provider.',
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => $lockedRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $lockedRequest->request_number.'_accepted_'.$lockedRequest->customer_id
            );

            app(EmailNotificationService::class)->sendRequestAcceptedOrAssignedToCustomer(
                $lockedRequest,
                $bid->provider
            );

            return true;
        });
    }
}
