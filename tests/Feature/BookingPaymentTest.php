<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Mail\BookingCreatedCustomerMail;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\Payment;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\Tip;
use App\Models\User;
use App\Services\Payment\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class BookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    private FakeStripeGateway $stripe;

    private User $customer;

    private User $provider;

    private User $technician;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET, 'services.stripe.currency' => 'usd']);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->provider = User::factory()->create(['role' => UserRole::Provider]);
        $this->technician = User::factory()->create(['role' => UserRole::Technician]);

        ProviderProfile::create([
            'user_id' => $this->provider->id,
            'company_name' => 'Fixit Co',
            'is_approved' => true,
            'is_accepting_jobs' => true,
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'service_radius_km' => 50,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
            'minimum_booking_charge' => 50.00,
        ]);
    }

    public function test_new_request_awaits_deposit_and_is_not_sent_to_providers(): void
    {
        $response = $this->createRequestViaApi();

        $response->assertCreated()
            ->assertJsonPath('data.status', ServiceRequestStatus::PendingPayment->value)
            ->assertJsonPath('data.deposit_amount', 50)
            ->assertJsonPath('data.deposit_paid', false)
            ->assertJsonPath('data.upfront_deposit_paid', 0)
            ->assertJsonPath('data.payment_status', 'deposit_pending');

        $this->assertSame(0, ProviderJobRequest::count());
        Mail::assertNotSent(BookingCreatedCustomerMail::class);
    }

    public function test_paying_the_deposit_sends_the_request_to_providers(): void
    {
        $requestId = $this->createRequestViaApi()->json('data.id');

        $intent = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-intent", ['type' => 'deposit'])
            ->assertOk()
            ->assertJsonPath('data.amount', 50)
            ->assertJsonPath('data.currency', 'usd')
            ->assertJsonPath('data.publishable_key', 'pk_test_fake')
            ->json('data');

        $this->assertNotEmpty($intent['client_secret']);
        $this->assertDatabaseHas('payments', [
            'transaction_id' => $intent['payment_intent_id'],
            'type' => 'deposit',
            'status' => PaymentStatus::Pending->value,
        ]);

        // Payment sheet not completed yet.
        $this->confirm($requestId, $intent['payment_intent_id'])->assertStatus(409);
        $this->assertSame(ServiceRequestStatus::PendingPayment, ServiceRequest::find($requestId)->status);

        $this->stripe->succeed($intent['payment_intent_id']);

        $this->confirm($requestId, $intent['payment_intent_id'])
            ->assertOk()
            ->assertJsonPath('data.summary.deposit_paid', true)
            ->assertJsonPath('data.summary.payment_status', 'deposit_paid');

        // Confirming twice (or webhook + app) is harmless.
        $this->confirm($requestId, $intent['payment_intent_id'])->assertOk();

        $serviceRequest = ServiceRequest::find($requestId);
        $this->assertSame(ServiceRequestStatus::Submitted, $serviceRequest->status);
        $this->assertNotNull($serviceRequest->deposit_paid_at);
        $this->assertSame(1, Payment::where('status', PaymentStatus::Successful)->count());
        $this->assertSame(1, ProviderJobRequest::where('service_request_id', $requestId)->count());
        Mail::assertSent(BookingCreatedCustomerMail::class);
    }

    public function test_repeated_deposit_intent_requests_reuse_the_open_intent(): void
    {
        $requestId = $this->createRequestViaApi()->json('data.id');

        $first = $this->depositIntent($requestId)->json('data.payment_intent_id');
        $second = $this->depositIntent($requestId)->json('data.payment_intent_id');

        $this->assertSame($first, $second);
        $this->assertSame(1, Payment::count());
    }

    public function test_deposit_cannot_be_paid_twice(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::Submitted);

        $this->depositIntent($serviceRequest->id)->assertStatus(422);
    }

    public function test_balance_cannot_be_paid_before_the_job_is_completed(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::InProgress);
        $this->makeAssignment($serviceRequest, JobAssignmentStatus::InProgress);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-intent", ['type' => 'balance'])
            ->assertStatus(422);
    }

    public function test_balance_payment_charges_invoice_total_minus_deposit_plus_tip(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::Completed);
        $this->makeAssignment($serviceRequest, JobAssignmentStatus::Completed);
        $invoice = $this->makeInvoice($serviceRequest, 200.00);

        $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-summary")
            ->assertOk()
            ->assertJsonPath('data.balance_due', 150)
            ->assertJsonPath('data.can_pay_balance', true);

        $intent = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-intent", [
                'type' => 'balance',
                'tip_amount' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('data.amount', 160)
            ->json('data');

        $this->stripe->succeed($intent['payment_intent_id']);
        $this->confirm($serviceRequest->id, $intent['payment_intent_id'])
            ->assertOk()
            ->assertJsonPath('data.summary.is_paid', true)
            ->assertJsonPath('data.summary.payment_status', 'paid');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertEquals(10.00, (float) $invoice->tip_amount);
        $this->assertEquals(210.00, (float) $invoice->total);
        $this->assertSame(1, ProviderEarning::where('invoice_id', $invoice->id)->count());
        $this->assertSame(1, Tip::where('service_request_id', $serviceRequest->id)->count());
    }

    public function test_changing_the_tip_replaces_the_open_balance_intent(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::Completed);
        $this->makeAssignment($serviceRequest, JobAssignmentStatus::Completed);
        $this->makeInvoice($serviceRequest, 200.00);

        $first = $this->balanceIntent($serviceRequest->id, 0)->json('data.payment_intent_id');
        $second = $this->balanceIntent($serviceRequest->id, 20)->assertJsonPath('data.amount', 170)->json('data.payment_intent_id');

        $this->assertNotSame($first, $second);
        $this->assertSame('canceled', $this->stripe->intents[$first]['status']);
        $this->assertSame(PaymentStatus::Failed, Payment::where('transaction_id', $first)->first()->status);
    }

    public function test_nothing_is_charged_when_the_deposit_covers_the_invoice(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::Completed);
        $this->makeAssignment($serviceRequest, JobAssignmentStatus::Completed);
        $invoice = $this->makeInvoice($serviceRequest, 40.00);

        $this->balanceIntent($serviceRequest->id, 0)
            ->assertOk()
            ->assertJsonPath('data.requires_payment', false);

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertEmpty($this->stripe->intents);
    }

    public function test_completing_the_job_issues_the_invoice_for_the_balance(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::InProgress);
        $assignment = $this->makeAssignment($serviceRequest, JobAssignmentStatus::InProgress);

        $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/v1/technician/jobs/{$assignment->id}/complete")
            ->assertOk();

        $this->assertSame(1, Invoice::where('service_request_id', $serviceRequest->id)->count());
        $this->assertSame(InvoiceStatus::Issued, $serviceRequest->invoices()->first()->status);

        $this->actingAs($this->technician, 'sanctum')
            ->getJson("/api/v1/technician/jobs/{$assignment->id}/invoice")
            ->assertOk()
            ->assertJsonPath('data.is_paid', false)
            ->assertJsonPath('data.payment_status', 'awaiting_customer_payment')
            ->assertJsonPath('data.deposit_paid', 50);
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $this->call('POST', '/api/v1/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad',
            'CONTENT_TYPE' => 'application/json',
        ], '{"id":"evt_1","type":"payment_intent.succeeded"}')->assertStatus(400);
    }

    public function test_webhook_success_confirms_the_deposit(): void
    {
        $requestId = $this->createRequestViaApi()->json('data.id');
        $intentId = $this->depositIntent($requestId)->json('data.payment_intent_id');

        $this->postWebhook('payment_intent.succeeded', $intentId, amountReceived: 5000)->assertOk();

        $this->assertSame(ServiceRequestStatus::Submitted, ServiceRequest::find($requestId)->status);
        $this->assertSame(PaymentStatus::Successful, Payment::where('transaction_id', $intentId)->first()->status);
    }

    public function test_webhook_with_short_amount_is_not_applied(): void
    {
        $requestId = $this->createRequestViaApi()->json('data.id');
        $intentId = $this->depositIntent($requestId)->json('data.payment_intent_id');

        $this->postWebhook('payment_intent.succeeded', $intentId, amountReceived: 100)->assertOk();

        $this->assertSame(ServiceRequestStatus::PendingPayment, ServiceRequest::find($requestId)->status);
        $this->assertSame(PaymentStatus::Pending, Payment::where('transaction_id', $intentId)->first()->status);
    }

    public function test_customer_cannot_pay_for_someone_elses_request(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: false, status: ServiceRequestStatus::PendingPayment);
        $other = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-intent", ['type' => 'deposit'])
            ->assertNotFound();
    }

    public function test_client_priced_and_cash_settlement_endpoints_are_gone(): void
    {
        $serviceRequest = $this->makeRequest(depositPaid: true, status: ServiceRequestStatus::Completed);
        $assignment = $this->makeAssignment($serviceRequest, JobAssignmentStatus::Completed);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/pay", ['amount' => 1])
            ->assertNotFound();

        $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/v1/technician/jobs/{$assignment->id}/settle")
            ->assertNotFound();
    }

    private function createRequestViaApi(): TestResponse
    {
        return $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/requests', [
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Kitchen sink is leaking',
            'address_line' => '1 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);
    }

    private function depositIntent(int $requestId): TestResponse
    {
        return $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-intent", ['type' => 'deposit']);
    }

    private function balanceIntent(int $requestId, float $tip): TestResponse
    {
        return $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-intent", ['type' => 'balance', 'tip_amount' => $tip]);
    }

    private function confirm(int $requestId, string $intentId): TestResponse
    {
        return $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-confirm", ['payment_intent_id' => $intentId]);
    }

    private function postWebhook(string $type, string $intentId, int $amountReceived): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => [
                'id' => $intentId,
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'amount' => $amountReceived,
                'amount_received' => $amountReceived,
                'currency' => 'usd',
            ]],
        ]);

        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', '/api/v1/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    private function makeRequest(bool $depositPaid, ServiceRequestStatus $status): ServiceRequest
    {
        return ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'direct',
            'target_provider_id' => $this->provider->id,
            'description' => 'Fix pipe',
            'address_line' => '1 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
            'status' => $status,
            'deposit_amount' => 50.00,
            'deposit_paid_at' => $depositPaid ? now() : null,
        ]);
    }

    private function makeAssignment(ServiceRequest $serviceRequest, JobAssignmentStatus $status): JobAssignment
    {
        return JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => $status,
        ]);
    }

    private function makeInvoice(ServiceRequest $serviceRequest, float $total): Invoice
    {
        return Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $this->provider->id,
            'subtotal' => $total,
            'tax' => 0,
            'discount' => 0,
            'tip_amount' => 0,
            'total' => $total,
            'status' => InvoiceStatus::Issued,
        ]);
    }
}
