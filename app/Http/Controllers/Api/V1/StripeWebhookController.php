<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentFlowException;
use App\Http\Controllers\Controller;
use App\Services\Payment\BookingPaymentService;
use App\Services\Payment\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use UnexpectedValueException;

/**
 * Stripe webhook: the authoritative signal that a deposit or balance PaymentIntent succeeded or failed.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeGateway $stripe, BookingPaymentService $bookingPayments): JsonResponse
    {
        try {
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
            );
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid webhook signature.'], 400);
        }

        $intent = $event->data->object ?? null;

        if (! $intent instanceof PaymentIntent) {
            return response()->json(['success' => true, 'message' => 'Event ignored.']);
        }

        try {
            match ($event->type) {
                'payment_intent.succeeded' => $bookingPayments->applySucceededIntent($intent),
                'payment_intent.payment_failed', 'payment_intent.canceled' => $bookingPayments->markIntentFailed($intent),
                default => null,
            };
        } catch (PaymentFlowException $e) {
            // Unknown or mismatched intents are logged and acknowledged so Stripe stops retrying.
            Log::warning('Stripe webhook not applied', ['event' => $event->id, 'type' => $event->type, 'error' => $e->getMessage()]);
        }

        return response()->json(['success' => true]);
    }
}
