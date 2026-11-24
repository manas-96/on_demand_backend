<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\Payment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\StripeGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class FakePaymentDriverTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.payments.driver' => 'fake', 'services.stripe.secret' => null]);
        $this->app->forgetInstance(StripeGateway::class);

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
            'minimum_booking_charge' => 50.00,
        ]);
    }

    public function test_fake_driver_is_used_when_configured(): void
    {
        $this->assertInstanceOf(FakePaymentGateway::class, app(StripeGateway::class));
    }

    public function test_deposit_completes_without_stripe_keys(): void
    {
        $requestId = $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/requests', [
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaking tap',
            'address_line' => '1 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ])->assertCreated()->json('data.id');

        $intent = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-intent", ['type' => 'deposit'])
            ->assertOk()
            ->assertJsonPath('data.gateway', 'fake')
            ->assertJsonPath('data.amount', 50)
            ->json('data');

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-confirm", ['payment_intent_id' => $intent['payment_intent_id']])
            ->assertOk()
            ->assertJsonPath('data.summary.deposit_paid', true);

        $this->assertSame(ServiceRequestStatus::Submitted, ServiceRequest::find($requestId)->status);
        $this->assertSame('fake', Payment::first()->payment_gateway);
    }

    public function test_balance_completes_without_stripe_keys(): void
    {
        $provider = User::factory()->create(['role' => UserRole::Provider]);
        $technician = User::factory()->create(['role' => UserRole::Technician]);
        $serviceRequest = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'direct',
            'target_provider_id' => $provider->id,
            'description' => 'Fix pipe',
            'address_line' => '1 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
            'deposit_amount' => 50.00,
            'deposit_paid_at' => now(),
        ]);
        JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $provider->id,
            'technician_id' => $technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Completed,
        ]);
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $provider->id,
            'subtotal' => 200,
            'tax' => 0,
            'discount' => 0,
            'tip_amount' => 0,
            'total' => 200,
            'status' => InvoiceStatus::Issued,
        ]);

        $intentId = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-intent", ['type' => 'balance', 'tip_amount' => 5])
            ->assertOk()
            ->assertJsonPath('data.amount', 155)
            ->json('data.payment_intent_id');

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/payment-confirm", ['payment_intent_id' => $intentId])
            ->assertOk()
            ->assertJsonPath('data.summary.is_paid', true);

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
    }

    public function test_fake_driver_is_refused_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->app->forgetInstance(StripeGateway::class);

        $this->expectException(RuntimeException::class);

        app(StripeGateway::class);
    }

    public function test_fake_driver_can_be_explicitly_allowed_on_a_production_test_server(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['services.payments.allow_fake_in_production' => true]);
        $this->app->forgetInstance(StripeGateway::class);

        $this->assertInstanceOf(FakePaymentGateway::class, app(StripeGateway::class));
    }
}
