<?php

namespace App\Services;

use App\Enums\JobAssignmentStatus;
use App\Enums\UserRole;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingCompletedMail;
use App\Mail\BookingCreatedCustomerMail;
use App\Mail\BookingRescheduledMail;
use App\Mail\ProviderApprovedMail;
use App\Mail\ProviderRegisteredAdminMail;
use App\Mail\QuoteAcceptedByCustomerProviderMail;
use App\Mail\QuoteApprovedByProviderCustomerMail;
use App\Mail\RequestAcceptedOrAssignedCustomerMail;
use App\Mail\ServiceRequestCreatedProviderMail;
use App\Mail\TechnicianAssignedMail;
use App\Models\JobAssignment;
use App\Models\ProviderProfile;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailNotificationService
{
    /**
     * Check and atomically lock email deduplication to prevent duplicate emails upon job retries or duplicate events.
     * Lock lasts 24 hours.
     */
    public function shouldSendEmail(string $eventType, int|string $requestId, string $recipientEmail, ?string $extraKey = null): bool
    {
        $hash = md5("{$eventType}:{$requestId}:{$recipientEmail}:{$extraKey}");
        $lockKey = "mail_dedup:{$hash}";

        $acquired = Cache::add($lockKey, now()->timestamp, now()->addHours(24));

        if (! $acquired) {
            Log::info("EmailNotificationService: Skipping duplicate email [{$eventType}] for request #{$requestId} to {$recipientEmail}");

            return false;
        }

        return true;
    }

    /**
     * Release deduplication lock if queue dispatch or mail preparation encounters a fatal error,
     * ensuring legitimate retries are not suppressed.
     */
    public function releaseDeduplicationLock(string $eventType, int|string $requestId, string $recipientEmail, ?string $extraKey = null): void
    {
        $hash = md5("{$eventType}:{$requestId}:{$recipientEmail}:{$extraKey}");
        $lockKey = "mail_dedup:{$hash}";

        Cache::forget($lockKey);
    }

    /**
     * Trigger 1a: Send booking confirmation email to customer when service request is created.
     */
    public function sendBookingCreatedToCustomer(ServiceRequest $serviceRequest): void
    {
        $customer = $serviceRequest->customer;

        if (! $customer || empty($customer->email)) {
            Log::warning('EmailNotificationService sendBookingCreatedToCustomer skipped: Customer or customer email missing.', [
                'request_id' => $serviceRequest->id,
            ]);

            return;
        }

        if (! $this->shouldSendEmail('booking_created_customer', $serviceRequest->id, $customer->email)) {
            return;
        }

        try {
            Mail::to($customer->email)->send(new BookingCreatedCustomerMail($serviceRequest, $customer));
        } catch (Throwable $e) {
            $this->releaseDeduplicationLock('booking_created_customer', $serviceRequest->id, $customer->email);
            Log::error('EmailNotificationService sendBookingCreatedToCustomer failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
                'customer_id' => $customer->id,
            ]);
        }
    }

    /**
     * Trigger 1b: Send email to nearby/matched providers when a service request is created.
     */
    public function sendRequestCreatedToProviders(ServiceRequest $serviceRequest, array|Collection $providers): void
    {
        try {
            foreach ($providers as $provider) {
                if (! $provider instanceof User && is_numeric($provider)) {
                    $provider = User::find($provider);
                }

                if ($provider instanceof User && ! empty($provider->email)) {
                    if (! $this->shouldSendEmail('request_created_provider', $serviceRequest->id, $provider->email)) {
                        continue;
                    }

                    try {
                        Mail::to($provider->email)->send(new ServiceRequestCreatedProviderMail($serviceRequest, $provider));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('request_created_provider', $serviceRequest->id, $provider->email);
                        Log::error('EmailNotificationService sendRequestCreatedToProviders inner failure: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                            'provider_id' => $provider->id,
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error('EmailNotificationService sendRequestCreatedToProviders failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Trigger 2: Send email to customer when service request is accepted by provider or technician assigned.
     */
    public function sendRequestAcceptedOrAssignedToCustomer(ServiceRequest $serviceRequest, ?User $provider = null, ?User $technician = null): void
    {
        $customer = $serviceRequest->customer;

        if (! $customer || empty($customer->email)) {
            Log::warning('EmailNotificationService sendRequestAcceptedOrAssignedToCustomer skipped: Customer or customer email missing.', [
                'request_id' => $serviceRequest->id,
            ]);

            return;
        }

        $extraKey = $technician ? 'tech_'.$technician->id : ($provider ? 'provider_'.$provider->id : 'general');

        if (! $this->shouldSendEmail('request_accepted_customer', $serviceRequest->id, $customer->email, $extraKey)) {
            return;
        }

        try {
            Mail::to($customer->email)->send(new RequestAcceptedOrAssignedCustomerMail($serviceRequest, $provider, $technician));
        } catch (Throwable $e) {
            $this->releaseDeduplicationLock('request_accepted_customer', $serviceRequest->id, $customer->email, $extraKey);
            Log::error('EmailNotificationService sendRequestAcceptedOrAssignedToCustomer failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Trigger 2b: Send email to assigned technician when technician is assigned.
     */
    public function sendTechnicianAssignedToTechnician(ServiceRequest $serviceRequest, ?User $technician = null, ?User $provider = null): void
    {
        try {
            if (! $technician) {
                $assignment = JobAssignment::where('service_request_id', $serviceRequest->id)->latest()->first();
                $technician = $assignment?->technician;
            }

            if (! $technician || empty($technician->email)) {
                Log::warning('EmailNotificationService sendTechnicianAssignedToTechnician skipped: No valid technician or email found.', [
                    'request_id' => $serviceRequest->id,
                ]);

                return;
            }

            if (! $this->shouldSendEmail('technician_assigned', $serviceRequest->id, $technician->email, 'tech_'.$technician->id)) {
                return;
            }

            try {
                Mail::to($technician->email)->send(new TechnicianAssignedMail($serviceRequest, $technician, $provider));
            } catch (Throwable $e) {
                $this->releaseDeduplicationLock('technician_assigned', $serviceRequest->id, $technician->email, 'tech_'.$technician->id);
                Log::error('EmailNotificationService sendTechnicianAssignedToTechnician inner failure: '.$e->getMessage(), [
                    'request_id' => $serviceRequest->id,
                    'technician_id' => $technician->id,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('EmailNotificationService sendTechnicianAssignedToTechnician failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
                'technician_id' => $technician?->id,
            ]);
        }
    }

    /**
     * Trigger 3: Send email to customer when onsite quotation is approved by provider.
     */
    public function sendQuoteApprovedToCustomer(ServiceRequest $serviceRequest, mixed $quote): void
    {
        $customer = $serviceRequest->customer;

        if (! $customer || empty($customer->email)) {
            Log::warning('EmailNotificationService sendQuoteApprovedToCustomer skipped: Customer or customer email missing.', [
                'request_id' => $serviceRequest->id,
            ]);

            return;
        }

        $quoteId = isset($quote->id) ? (string) $quote->id : '0';

        if (! $this->shouldSendEmail('quote_approved_customer', $serviceRequest->id, $customer->email, 'quote_'.$quoteId)) {
            return;
        }

        try {
            Mail::to($customer->email)->send(new QuoteApprovedByProviderCustomerMail($serviceRequest, $quote));
        } catch (Throwable $e) {
            $this->releaseDeduplicationLock('quote_approved_customer', $serviceRequest->id, $customer->email, 'quote_'.$quoteId);
            Log::error('EmailNotificationService sendQuoteApprovedToCustomer failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Trigger 4: Send email to provider when customer accepts quotation.
     */
    public function sendQuoteAcceptedToProvider(ServiceRequest $serviceRequest, mixed $quote, ?User $provider = null): void
    {
        $targetProvider = $provider ?? $serviceRequest->targetProvider;
        if (! $targetProvider && isset($quote->provider)) {
            $targetProvider = $quote->provider;
        }

        if (! $targetProvider || empty($targetProvider->email)) {
            Log::warning('EmailNotificationService sendQuoteAcceptedToProvider skipped: Provider or provider email missing.', [
                'request_id' => $serviceRequest->id,
            ]);

            return;
        }

        $quoteId = isset($quote->id) ? (string) $quote->id : '0';

        if (! $this->shouldSendEmail('quote_accepted_provider', $serviceRequest->id, $targetProvider->email, 'quote_'.$quoteId)) {
            return;
        }

        try {
            Mail::to($targetProvider->email)->send(new QuoteAcceptedByCustomerProviderMail($serviceRequest, $quote, $targetProvider));
        } catch (Throwable $e) {
            $this->releaseDeduplicationLock('quote_accepted_provider', $serviceRequest->id, $targetProvider->email, 'quote_'.$quoteId);
            Log::error('EmailNotificationService sendQuoteAcceptedToProvider failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Trigger 5: Send email to Customer, Provider, and Technician when booking is cancelled.
     */
    public function sendBookingCancelled(ServiceRequest $serviceRequest, string $cancelledBy = 'customer', ?string $reason = null): void
    {
        try {
            // Customer notification
            $customer = $serviceRequest->customer;
            if ($customer && ! empty($customer->email)) {
                if ($this->shouldSendEmail('booking_cancelled', $serviceRequest->id, $customer->email, 'customer_'.$cancelledBy)) {
                    try {
                        Mail::to($customer->email)->send(new BookingCancelledMail($serviceRequest, $customer, 'customer', $cancelledBy, $reason));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_cancelled', $serviceRequest->id, $customer->email, 'customer_'.$cancelledBy);
                        Log::error('EmailNotificationService sendBookingCancelled customer dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }

            // Provider notification
            $assignment = $serviceRequest->jobAssignments()->latest()->first() ?? $serviceRequest->jobAssignment;
            $provider = $assignment?->provider ?? $serviceRequest->targetProvider;

            if ($provider && ! empty($provider->email) && ($cancelledBy !== 'provider' || $provider->id !== $customer?->id)) {
                if ($this->shouldSendEmail('booking_cancelled', $serviceRequest->id, $provider->email, 'provider_'.$cancelledBy)) {
                    try {
                        Mail::to($provider->email)->send(new BookingCancelledMail($serviceRequest, $provider, 'provider', $cancelledBy, $reason));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_cancelled', $serviceRequest->id, $provider->email, 'provider_'.$cancelledBy);
                        Log::error('EmailNotificationService sendBookingCancelled provider dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }

            // Technician notification
            $technician = $assignment?->technician;
            if ($technician && ! empty($technician->email) && $cancelledBy !== 'technician') {
                if ($this->shouldSendEmail('booking_cancelled', $serviceRequest->id, $technician->email, 'technician_'.$cancelledBy)) {
                    try {
                        Mail::to($technician->email)->send(new BookingCancelledMail($serviceRequest, $technician, 'technician', $cancelledBy, $reason));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_cancelled', $serviceRequest->id, $technician->email, 'technician_'.$cancelledBy);
                        Log::error('EmailNotificationService sendBookingCancelled technician dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error('EmailNotificationService sendBookingCancelled failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Email the booking's provider and assigned technician that the customer moved the date/time.
     * Keyed on the new schedule, so each later reschedule sends again.
     */
    public function sendBookingRescheduled(ServiceRequest $serviceRequest, string $previousSchedule): void
    {
        try {
            $assignment = $serviceRequest->jobAssignments()
                ->where('status', '!=', JobAssignmentStatus::Declined->value)
                ->latest()
                ->first();

            $recipients = [
                'provider' => $assignment?->provider ?? $serviceRequest->targetProvider,
                'technician' => $assignment?->technician,
            ];
            $scheduleKey = $serviceRequest->preferred_date?->toDateString().' '.$serviceRequest->preferred_start_time;

            foreach ($recipients as $role => $user) {
                if (! $user || empty($user->email)) {
                    continue;
                }

                $dedupKey = $role.'_'.$scheduleKey;

                if (! $this->shouldSendEmail('booking_rescheduled', $serviceRequest->id, $user->email, $dedupKey)) {
                    continue;
                }

                try {
                    Mail::to($user->email)->send(new BookingRescheduledMail($serviceRequest, $user, $role, $previousSchedule));
                } catch (Throwable $e) {
                    $this->releaseDeduplicationLock('booking_rescheduled', $serviceRequest->id, $user->email, $dedupKey);
                    Log::error("EmailNotificationService sendBookingRescheduled {$role} dispatch failed: ".$e->getMessage(), [
                        'request_id' => $serviceRequest->id,
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::error('EmailNotificationService sendBookingRescheduled failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Trigger 6: Send email to Customer, Provider, and Technician when booking is completed.
     */
    public function sendBookingCompleted(ServiceRequest $serviceRequest, JobAssignment $assignment): void
    {
        try {
            $customer = $serviceRequest->customer;
            if ($customer && ! empty($customer->email)) {
                if ($this->shouldSendEmail('booking_completed', $serviceRequest->id, $customer->email, 'customer')) {
                    try {
                        Mail::to($customer->email)->send(new BookingCompletedMail($serviceRequest, $assignment, $customer, 'customer'));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_completed', $serviceRequest->id, $customer->email, 'customer');
                        Log::error('EmailNotificationService sendBookingCompleted customer dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }

            $provider = $assignment->provider ?? $serviceRequest->targetProvider;
            if ($provider && ! empty($provider->email)) {
                if ($this->shouldSendEmail('booking_completed', $serviceRequest->id, $provider->email, 'provider')) {
                    try {
                        Mail::to($provider->email)->send(new BookingCompletedMail($serviceRequest, $assignment, $provider, 'provider'));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_completed', $serviceRequest->id, $provider->email, 'provider');
                        Log::error('EmailNotificationService sendBookingCompleted provider dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }

            $technician = $assignment->technician;
            if ($technician && ! empty($technician->email)) {
                if ($this->shouldSendEmail('booking_completed', $serviceRequest->id, $technician->email, 'technician')) {
                    try {
                        Mail::to($technician->email)->send(new BookingCompletedMail($serviceRequest, $assignment, $technician, 'technician'));
                    } catch (Throwable $e) {
                        $this->releaseDeduplicationLock('booking_completed', $serviceRequest->id, $technician->email, 'technician');
                        Log::error('EmailNotificationService sendBookingCompleted technician dispatch failed: '.$e->getMessage(), [
                            'request_id' => $serviceRequest->id,
                        ]);
                    }
                }
            }
        } catch (Throwable $e) {
            Log::error('EmailNotificationService sendBookingCompleted failed: '.$e->getMessage(), [
                'request_id' => $serviceRequest->id,
            ]);
        }
    }

    /**
     * Tell every active admin that a self-registered provider verified their email and awaits approval.
     */
    public function sendProviderRegisteredToAdmins(ProviderProfile $providerProfile): void
    {
        $providerProfile->loadMissing('user');

        $admins = User::where('role', UserRole::Admin)->where('is_active', true)->whereNotNull('email')->get();

        foreach ($admins as $admin) {
            if (! $this->shouldSendEmail('provider_registered_admin', $providerProfile->id, $admin->email)) {
                continue;
            }

            try {
                Mail::to($admin->email)->send(new ProviderRegisteredAdminMail($providerProfile, $admin));
            } catch (Throwable $e) {
                $this->releaseDeduplicationLock('provider_registered_admin', $providerProfile->id, $admin->email);
                Log::error('EmailNotificationService sendProviderRegisteredToAdmins failed: '.$e->getMessage(), [
                    'provider_profile_id' => $providerProfile->id,
                    'admin_id' => $admin->id,
                ]);
            }
        }
    }

    /**
     * Tell a provider their account was approved by an admin.
     */
    public function sendProviderApproved(ProviderProfile $providerProfile): void
    {
        $provider = $providerProfile->user;

        if (! $provider || empty($provider->email)) {
            return;
        }

        if (! $this->shouldSendEmail('provider_approved', $providerProfile->id, $provider->email)) {
            return;
        }

        try {
            Mail::to($provider->email)->send(new ProviderApprovedMail($providerProfile, $provider));
        } catch (Throwable $e) {
            $this->releaseDeduplicationLock('provider_approved', $providerProfile->id, $provider->email);
            Log::error('EmailNotificationService sendProviderApproved failed: '.$e->getMessage(), [
                'provider_profile_id' => $providerProfile->id,
            ]);
        }
    }
}
