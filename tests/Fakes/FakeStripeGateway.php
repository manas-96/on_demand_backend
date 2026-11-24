<?php

namespace Tests\Fakes;

use App\Services\Payment\StripeGateway;
use Stripe\PaymentIntent;

/**
 * In-memory Stripe stand-in: PaymentIntents are created/retrieved locally; webhook signatures use the real verifier.
 */
class FakeStripeGateway extends StripeGateway
{
    /** @var array<string, array<string, mixed>> */
    public array $intents = [];

    public function publishableKey(): ?string
    {
        return 'pk_test_fake';
    }

    public function createPaymentIntent(float $amount, array $metadata, ?string $description = null, ?string $idempotencyKey = null): PaymentIntent
    {
        $id = 'pi_test_'.(count($this->intents) + 1);

        $this->intents[$id] = [
            'id' => $id,
            'object' => 'payment_intent',
            'client_secret' => $id.'_secret_fake',
            'status' => 'requires_payment_method',
            'amount' => $this->toMinorUnits($amount),
            'amount_received' => 0,
            'currency' => $this->currency(),
            'metadata' => $metadata,
        ];

        return PaymentIntent::constructFrom($this->intents[$id]);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::constructFrom($this->intents[$paymentIntentId]);
    }

    public function cancelPaymentIntent(string $paymentIntentId): void
    {
        $this->intents[$paymentIntentId]['status'] = 'canceled';
    }

    /**
     * Simulate the customer completing the payment sheet.
     */
    public function succeed(string $paymentIntentId): void
    {
        $this->intents[$paymentIntentId]['status'] = 'succeeded';
        $this->intents[$paymentIntentId]['amount_received'] = $this->intents[$paymentIntentId]['amount'];
    }
}
