<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;

/**
 * Development stand-in for Stripe (PAYMENT_DRIVER=fake): every PaymentIntent succeeds immediately.
 *
 * Amounts, statuses and payment records follow the exact same path as real Stripe payments,
 * so switching to PAYMENT_DRIVER=stripe later needs no other change. Never enable it in production.
 */
class FakePaymentGateway extends StripeGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function publishableKey(): ?string
    {
        return null;
    }

    public function createPaymentIntent(float $amount, array $metadata, ?string $description = null, ?string $idempotencyKey = null): PaymentIntent
    {
        $id = 'fake_pi_'.Str::lower(Str::random(24));

        return $this->succeededIntent($id, $this->toMinorUnits($amount), $this->currency());
    }

    /**
     * Rebuilt from our own payment row, since there is no remote state to fetch.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        $payment = Payment::where('transaction_id', $paymentIntentId)->first();

        if (! $payment) {
            throw new RuntimeException("Unknown fake PaymentIntent {$paymentIntentId}.");
        }

        return $this->succeededIntent(
            $paymentIntentId,
            $this->toMinorUnits((float) $payment->amount),
            (string) ($payment->currency ?? $this->currency()),
        );
    }

    public function cancelPaymentIntent(string $paymentIntentId): void
    {
        //
    }

    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        throw new RuntimeException('Webhooks are not available with the fake payment driver.');
    }

    private function succeededIntent(string $id, int $amountMinor, string $currency): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id' => $id,
            'object' => 'payment_intent',
            'client_secret' => $id.'_secret_fake',
            'status' => PaymentIntent::STATUS_SUCCEEDED,
            'amount' => $amountMinor,
            'amount_received' => $amountMinor,
            'currency' => $currency,
        ]);
    }
}
