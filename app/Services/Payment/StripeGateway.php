<?php

namespace App\Services\Payment;

use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Thin wrapper around the Stripe SDK so the rest of the app (and tests) never talk to Stripe directly.
 */
class StripeGateway
{
    private ?StripeClient $client = null;

    /**
     * Gateway identifier stored on payments and sent to the apps (`stripe` or `fake`).
     */
    public function name(): string
    {
        return 'stripe';
    }

    public function currency(): string
    {
        return strtolower((string) config('services.stripe.currency', 'usd'));
    }

    public function publishableKey(): ?string
    {
        return config('services.stripe.key');
    }

    /**
     * Create a PaymentIntent for the given amount (major units, e.g. dollars).
     *
     * @param  array<string, string>  $metadata
     */
    public function createPaymentIntent(float $amount, array $metadata, ?string $description = null, ?string $idempotencyKey = null): PaymentIntent
    {
        $params = [
            'amount' => $this->toMinorUnits($amount),
            'currency' => $this->currency(),
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ];

        if ($description) {
            $params['description'] = $description;
        }

        $options = $idempotencyKey ? ['idempotency_key' => $idempotencyKey] : [];

        return $this->client()->paymentIntents->create($params, $options);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId);
    }

    public function cancelPaymentIntent(string $paymentIntentId): void
    {
        $this->client()->paymentIntents->cancel($paymentIntentId);
    }

    /**
     * Verify a webhook payload's signature and build the Stripe event.
     */
    public function constructWebhookEvent(string $payload, string $signatureHeader): Event
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signatureHeader, $secret);
    }

    public function toMinorUnits(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public function fromMinorUnits(int $amount): float
    {
        return round($amount / 100, 2);
    }

    private function client(): StripeClient
    {
        if ($this->client === null) {
            $secret = config('services.stripe.secret');

            if (! $secret) {
                throw new RuntimeException('Stripe secret key is not configured.');
            }

            $this->client = new StripeClient($secret);
        }

        return $this->client;
    }
}
