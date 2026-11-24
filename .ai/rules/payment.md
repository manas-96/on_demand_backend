---
paths:
  - 'app/Services/Payment/**'
---

# Payment

## Customer payments are two-phase, Stripe-only, server-priced
Customers pay online via Stripe only (no cash): a deposit (category minimum booking charge, stored on service_requests.deposit_amount) at creation, then balance = invoice total − deposit + tip after job completion. Requests stay `pending_payment` and are not sent to providers until the deposit succeeds. All amounts are computed in BookingPaymentService — never accept an amount from the client. Apply Stripe results only via BookingPaymentService::applySucceededIntent (shared by payment-confirm endpoint and /stripe/webhook; idempotent on payments.transaction_id = PaymentIntent id). Tests swap StripeGateway for Tests\Fakes\FakeStripeGateway.

PAYMENT_DRIVER=fake swaps StripeGateway for FakePaymentGateway (bound in AppServiceProvider; instant success, no keys). It must stay refused in production unless PAYMENT_ALLOW_FAKE_IN_PRODUCTION=true. Apps branch on the `gateway` field of payment-intent, never on their own config.
