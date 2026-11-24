<?php

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Exceptions\PaymentFlowException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ServiceRequest;
use App\Models\Tip;
use App\Services\EmailNotificationService;
use App\Services\Invoice\InvoiceService;
use App\Services\Matching\ProviderMatchingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Throwable;

/**
 * Two-phase customer payments, all amounts decided server-side:
 *  1. Deposit  — the category's minimum booking charge, paid when the request is created.
 *               The request is only sent to providers once the deposit succeeds.
 *  2. Balance  — invoice total minus the deposit, plus an optional tip, paid after the job is completed.
 */
class BookingPaymentService
{
    /** PaymentIntent states in which the customer can still complete the payment. */
    private const REUSABLE_INTENT_STATES = ['requires_payment_method', 'requires_confirmation', 'requires_action'];

    public function __construct(
        private StripeGateway $stripe,
        private PaymentService $paymentService,
        private InvoiceService $invoiceService,
        private ProviderMatchingService $matchingService,
    ) {}

    /**
     * Server-computed deposit for a new request.
     */
    public function depositAmountFor(ServiceRequest $serviceRequest): float
    {
        $serviceRequest->loadMissing(['category', 'subCategory']);

        $amount = $serviceRequest->subCategory?->effective_minimum_booking_charge
            ?? $serviceRequest->category?->effective_minimum_booking_charge
            ?? 0.0;

        return round((float) $amount, 2);
    }

    /**
     * Create (or reuse) the Stripe PaymentIntent for the booking deposit.
     *
     * @return array<string, mixed>
     */
    public function createDepositIntent(ServiceRequest $serviceRequest): array
    {
        if ($serviceRequest->deposit_paid_at) {
            throw new PaymentFlowException('The booking deposit has already been paid.');
        }

        if ($serviceRequest->status !== ServiceRequestStatus::PendingPayment) {
            throw new PaymentFlowException('This request is not awaiting a deposit payment.');
        }

        $amount = (float) $serviceRequest->deposit_amount;

        if ($amount <= 0) {
            throw new PaymentFlowException('No deposit is configured for this service.');
        }

        return $this->intentFor($serviceRequest, PaymentType::Deposit, $amount, ['deposit' => $amount]);
    }

    /**
     * Create (or reuse) the Stripe PaymentIntent for the remaining balance plus tip.
     *
     * @return array<string, mixed>
     */
    public function createBalanceIntent(ServiceRequest $serviceRequest, float $tipAmount = 0.0): array
    {
        $breakdown = $this->balanceBreakdown($serviceRequest, $tipAmount, generateInvoice: true);

        if ($breakdown['is_paid']) {
            throw new PaymentFlowException('This request has already been paid in full.');
        }

        if (! $breakdown['can_pay_balance']) {
            throw new PaymentFlowException('The remaining balance can only be paid after the job is completed.');
        }

        $amount = $breakdown['amount_payable'];

        if ($amount <= 0) {
            // Deposit already covers the invoice and no tip was added: nothing to charge.
            $invoice = Invoice::findOrFail($breakdown['invoice_id']);
            $this->paymentService->finalizeInvoice($invoice);

            return ['requires_payment' => false] + $this->summary($serviceRequest->fresh());
        }

        return $this->intentFor($serviceRequest, PaymentType::Balance, $amount, [
            'invoice_id' => $breakdown['invoice_id'],
            'balance_due' => $breakdown['balance_due'],
            'tip_amount' => $breakdown['tip_amount'],
        ]);
    }

    /**
     * Customer-facing summary of what has been paid and what is due.
     *
     * @return array<string, mixed>
     */
    public function summary(ServiceRequest $serviceRequest): array
    {
        $breakdown = $this->balanceBreakdown($serviceRequest, 0.0, generateInvoice: false);

        return [
            'service_request_id' => $serviceRequest->id,
            'currency' => $this->stripe->currency(),
            'deposit_amount' => (float) $serviceRequest->deposit_amount,
            'deposit_paid' => (bool) $serviceRequest->deposit_paid_at,
            'deposit_paid_at' => $serviceRequest->deposit_paid_at?->toIso8601String(),
            'invoice_id' => $breakdown['invoice_id'],
            'invoice_total' => $breakdown['invoice_total'],
            'balance_due' => $breakdown['balance_due'],
            'can_pay_balance' => $breakdown['can_pay_balance'],
            'is_paid' => $breakdown['is_paid'],
            'payment_status' => $this->paymentStatusLabel($serviceRequest, $breakdown['is_paid']),
        ];
    }

    public function paymentStatusLabel(ServiceRequest $serviceRequest, ?bool $isPaid = null): string
    {
        $isPaid ??= $serviceRequest->invoices()->where('status', InvoiceStatus::Paid)->exists();

        return match (true) {
            $isPaid => 'paid',
            (bool) $serviceRequest->deposit_paid_at => 'deposit_paid',
            default => 'deposit_pending',
        };
    }

    /**
     * Re-fetch a PaymentIntent from Stripe and apply it. Used by the app after the payment sheet closes.
     */
    public function confirm(ServiceRequest $serviceRequest, string $paymentIntentId): Payment
    {
        $payment = Payment::where('transaction_id', $paymentIntentId)
            ->where('service_request_id', $serviceRequest->id)
            ->first();

        if (! $payment) {
            throw new PaymentFlowException('Payment not found for this request.');
        }

        if ($payment->status === PaymentStatus::Successful) {
            return $payment;
        }

        $intent = $this->stripe->retrievePaymentIntent($paymentIntentId);

        if ($intent->status !== PaymentIntent::STATUS_SUCCEEDED) {
            throw new PaymentFlowException("Payment has not succeeded yet (status: {$intent->status}).", 409);
        }

        return $this->applySucceededIntent($intent);
    }

    /**
     * Apply a succeeded PaymentIntent (from the confirm endpoint or the Stripe webhook). Idempotent.
     */
    public function applySucceededIntent(PaymentIntent $intent): Payment
    {
        $dispatchRequest = null;

        $payment = DB::transaction(function () use ($intent, &$dispatchRequest) {
            $payment = Payment::where('transaction_id', $intent->id)->lockForUpdate()->first();

            if (! $payment) {
                throw new PaymentFlowException("No payment record for PaymentIntent {$intent->id}.", 404);
            }

            if ($payment->status === PaymentStatus::Successful) {
                return $payment;
            }

            $received = $this->stripe->fromMinorUnits((int) ($intent->amount_received ?? 0));

            if ($received < (float) $payment->amount || strtolower((string) $intent->currency) !== strtolower((string) $payment->currency)) {
                throw new PaymentFlowException("PaymentIntent {$intent->id} amount/currency does not match the expected payment.", 422);
            }

            $payment->update([
                'status' => PaymentStatus::Successful,
                'paid_at' => now(),
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'stripe_status' => $intent->status,
                    'amount_received' => $received,
                ]),
            ]);

            $serviceRequest = ServiceRequest::lockForUpdate()->findOrFail($payment->service_request_id);

            if ($payment->type === PaymentType::Deposit) {
                if (! $serviceRequest->deposit_paid_at) {
                    $serviceRequest->update([
                        'deposit_paid_at' => now(),
                        'status' => ServiceRequestStatus::Submitted,
                    ]);
                    $dispatchRequest = $serviceRequest;
                }
            } else {
                $this->settleBalance($serviceRequest, $payment);
            }

            return $payment->fresh();
        });

        if ($dispatchRequest) {
            $this->announceNewRequest($dispatchRequest);
        }

        return $payment;
    }

    public function markIntentFailed(PaymentIntent $intent): void
    {
        Payment::where('transaction_id', $intent->id)
            ->where('status', PaymentStatus::Pending)
            ->update([
                'status' => PaymentStatus::Failed,
            ]);
    }

    /**
     * @return array{invoice_id: ?int, invoice_total: float, deposit_paid: float, balance_due: float, tip_amount: float, amount_payable: float, can_pay_balance: bool, is_paid: bool}
     */
    private function balanceBreakdown(ServiceRequest $serviceRequest, float $tipAmount, bool $generateInvoice): array
    {
        $invoice = $serviceRequest->invoices()->latest()->first();
        $isJobCompleted = $this->isJobCompleted($serviceRequest);

        if (! $invoice && $generateInvoice && $isJobCompleted) {
            $quote = $serviceRequest->quotes()->where('status', QuoteStatus::Approved)->latest()->first()
                ?? $serviceRequest->commercialQuote;
            $invoice = $this->invoiceService->generateInvoice($serviceRequest, $quote);
        }

        $isPaid = $invoice?->status === InvoiceStatus::Paid;
        $invoiceTotal = $invoice ? round((float) $invoice->total - (float) $invoice->tip_amount, 2) : 0.0;
        $depositPaid = $serviceRequest->depositPaidAmount();
        $balanceDue = $invoice && ! $isPaid ? max(0.0, round($invoiceTotal - $depositPaid, 2)) : 0.0;
        $tipAmount = max(0.0, round($tipAmount, 2));

        return [
            'invoice_id' => $invoice?->id,
            'invoice_total' => $invoiceTotal,
            'deposit_paid' => $depositPaid,
            'balance_due' => $balanceDue,
            'tip_amount' => $tipAmount,
            'amount_payable' => round($balanceDue + $tipAmount, 2),
            'can_pay_balance' => $isJobCompleted && (bool) $serviceRequest->deposit_paid_at && ! $isPaid,
            'is_paid' => $isPaid,
        ];
    }

    private function isJobCompleted(ServiceRequest $serviceRequest): bool
    {
        return $serviceRequest->status === ServiceRequestStatus::Completed
            || $serviceRequest->jobAssignments()->where('status', JobAssignmentStatus::Completed)->exists();
    }

    /**
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function intentFor(ServiceRequest $serviceRequest, PaymentType $type, float $amount, array $details): array
    {
        $currency = $this->stripe->currency();

        $pending = Payment::where('service_request_id', $serviceRequest->id)
            ->where('type', $type)
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->first();

        if ($pending && $pending->payment_gateway === $this->stripe->name() && $this->stripe->name() === 'fake') {
            // Fake intents have no remote state; drop the stale one and issue a new one.
            $pending->update(['status' => PaymentStatus::Failed]);
            $pending = null;
        }

        if ($pending) {
            $reused = $this->reusePendingIntent($pending, $amount, $currency);
            if ($reused) {
                return $this->intentResponse($serviceRequest, $type, $pending, $reused);
            }
        }

        $intent = $this->stripe->createPaymentIntent(
            $amount,
            [
                'service_request_id' => (string) $serviceRequest->id,
                'request_number' => (string) $serviceRequest->request_number,
                'payment_type' => $type->value,
            ],
            ucfirst($type->value)." payment for request #{$serviceRequest->request_number}",
        );

        $payment = Payment::create([
            'invoice_id' => $details['invoice_id'] ?? null,
            'service_request_id' => $serviceRequest->id,
            'type' => $type,
            'customer_id' => $serviceRequest->customer_id,
            'payment_gateway' => $this->stripe->name(),
            'transaction_id' => $intent->id,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentStatus::Pending,
            'payment_details' => $details,
        ]);

        return $this->intentResponse($serviceRequest, $type, $payment, $intent);
    }

    /**
     * Reuse an open PaymentIntent for the same amount; otherwise cancel it so it can't be paid twice.
     */
    private function reusePendingIntent(Payment $pending, float $amount, string $currency): ?PaymentIntent
    {
        try {
            $intent = $this->stripe->retrievePaymentIntent($pending->transaction_id);
        } catch (Throwable $e) {
            Log::warning('Stripe: could not retrieve pending PaymentIntent', ['id' => $pending->transaction_id, 'error' => $e->getMessage()]);
            $pending->update(['status' => PaymentStatus::Failed]);

            return null;
        }

        if ($intent->status === PaymentIntent::STATUS_SUCCEEDED) {
            $this->applySucceededIntent($intent);
            throw new PaymentFlowException('This payment has already been completed.');
        }

        $sameAmount = (float) $pending->amount === $amount && strtolower((string) $pending->currency) === $currency;

        if ($sameAmount && in_array($intent->status, self::REUSABLE_INTENT_STATES, true)) {
            return $intent;
        }

        if (in_array($intent->status, self::REUSABLE_INTENT_STATES, true)) {
            try {
                $this->stripe->cancelPaymentIntent($intent->id);
            } catch (Throwable $e) {
                Log::warning('Stripe: could not cancel stale PaymentIntent', ['id' => $intent->id, 'error' => $e->getMessage()]);
            }
        }

        $pending->update(['status' => PaymentStatus::Failed]);

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function intentResponse(ServiceRequest $serviceRequest, PaymentType $type, Payment $payment, PaymentIntent $intent): array
    {
        return [
            'requires_payment' => true,
            'gateway' => $this->stripe->name(),
            'payment_type' => $type->value,
            'payment_id' => $payment->id,
            'payment_intent_id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'publishable_key' => $this->stripe->publishableKey(),
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'breakdown' => $payment->payment_details,
            'service_request_id' => $serviceRequest->id,
        ];
    }

    private function settleBalance(ServiceRequest $serviceRequest, Payment $payment): void
    {
        $invoice = $payment->invoice_id
            ? Invoice::lockForUpdate()->find($payment->invoice_id)
            : $serviceRequest->invoices()->latest()->lockForUpdate()->first();

        if (! $invoice) {
            throw new PaymentFlowException('No invoice found for the balance payment.', 422);
        }

        if ($invoice->status === InvoiceStatus::Paid) {
            return;
        }

        $tipAmount = (float) ($payment->payment_details['tip_amount'] ?? 0.0);
        $preTipTotal = round((float) $invoice->total - (float) $invoice->tip_amount, 2);

        $invoice->update([
            'tip_amount' => $tipAmount,
            'total' => round($preTipTotal + $tipAmount, 2),
        ]);

        if (! $payment->invoice_id) {
            $payment->update(['invoice_id' => $invoice->id]);
        }

        if ($tipAmount > 0) {
            Tip::create([
                'service_request_id' => $serviceRequest->id,
                'customer_id' => $serviceRequest->customer_id,
                'provider_id' => $invoice->provider_id,
                'technician_id' => $serviceRequest->jobAssignments()->latest()->value('technician_id'),
                'amount' => $tipAmount,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        $this->paymentService->finalizeInvoice($invoice->fresh());
    }

    /**
     * After the deposit succeeds the request goes out to providers and the customer gets their confirmation.
     */
    private function announceNewRequest(ServiceRequest $serviceRequest): void
    {
        try {
            $this->matchingService->dispatchInvitations($serviceRequest);

            $serviceRequest->loadMissing(['customer', 'category']);
            app(EmailNotificationService::class)->sendBookingCreatedToCustomer($serviceRequest);
        } catch (Throwable $e) {
            Log::error('Failed to announce request after deposit payment', [
                'service_request_id' => $serviceRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
