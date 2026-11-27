<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Quote;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerQuoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $otherCustomer;

    protected User $provider;

    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->otherCustomer = User::factory()->create(['role' => UserRole::Customer]);
        $this->provider = User::factory()->create(['role' => UserRole::Provider]);
        $this->category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    }

    public function test_customer_approves_own_provider_approved_quote_by_booking_id(): void
    {
        $serviceRequest = $this->createRequest($this->customer, 'SR-20261128-000001');
        $quote = $this->createQuote($serviceRequest, approvedByProvider: true);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/quotes/{$serviceRequest->id}/approve");

        $response->assertOk()->assertJsonPath('data.id', $quote->id);
        $this->assertSame(QuoteStatus::Approved, $quote->fresh()->status);
        $this->assertSame(ServiceRequestStatus::InProgress, $serviceRequest->fresh()->status);
    }

    public function test_customer_cannot_approve_another_customers_quote(): void
    {
        $this->createRequest($this->customer, 'SR-20261128-000002');
        $othersRequest = $this->createRequest($this->otherCustomer, 'SR-20261128-000003');
        $othersQuote = $this->createQuote($othersRequest, approvedByProvider: false);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/quotes/{$othersQuote->id}/approve");

        $response->assertNotFound()->assertJsonPath('message', 'Quotation not found.');
        $othersQuote->refresh();
        $this->assertFalse($othersQuote->is_approved_by_provider);
        $this->assertSame(QuoteStatus::Pending, $othersQuote->status);
        $this->assertSame(ServiceRequestStatus::Assigned, $othersRequest->fresh()->status);
    }

    public function test_unknown_id_does_not_fall_back_to_the_latest_quote(): void
    {
        $othersRequest = $this->createRequest($this->otherCustomer, 'SR-20261128-000004');
        $othersQuote = $this->createQuote($othersRequest, approvedByProvider: true);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/customer/quotes/999999/approve');

        $response->assertNotFound();
        $this->assertSame(QuoteStatus::Pending, $othersQuote->fresh()->status);
    }

    public function test_customer_cannot_approve_quote_the_provider_has_not_approved(): void
    {
        $serviceRequest = $this->createRequest($this->customer, 'SR-20261128-000005');
        $quote = $this->createQuote($serviceRequest, approvedByProvider: false);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/quotes/{$quote->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Quote must be approved by provider before customer approval.');
        $quote->refresh();
        $this->assertFalse($quote->is_approved_by_provider);
        $this->assertSame(QuoteStatus::Pending, $quote->status);
    }

    public function test_approving_an_already_approved_quote_leaves_the_booking_alone(): void
    {
        $serviceRequest = $this->createRequest($this->customer, 'SR-20261128-000006', ServiceRequestStatus::Completed);
        $quote = $this->createQuote($serviceRequest, approvedByProvider: true, status: QuoteStatus::Approved);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/quotes/{$quote->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'This quotation is no longer awaiting your approval.');
        $this->assertSame(ServiceRequestStatus::Completed, $serviceRequest->fresh()->status);
    }

    public function test_guest_cannot_approve_a_quote(): void
    {
        $quote = $this->createQuote($this->createRequest($this->customer, 'SR-20261128-000007'), approvedByProvider: true);

        $this->postJson("/api/v1/customer/quotes/{$quote->id}/approve")->assertUnauthorized();
        $this->assertSame(QuoteStatus::Pending, $quote->fresh()->status);
    }

    private function createRequest(User $customer, string $requestNumber, ServiceRequestStatus $status = ServiceRequestStatus::Assigned): ServiceRequest
    {
        return ServiceRequest::create([
            'request_number' => $requestNumber,
            'customer_id' => $customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaking kitchen tap',
            'address_line' => '12 Pipe Street',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }

    private function createQuote(ServiceRequest $serviceRequest, bool $approvedByProvider, QuoteStatus $status = QuoteStatus::Pending): Quote
    {
        return Quote::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'labor_subtotal' => 100.00,
            'material_subtotal' => 50.00,
            'tax' => 7.50,
            'discount' => 0.00,
            'total' => 157.50,
            'status' => $status,
            'is_approved_by_provider' => $approvedByProvider,
        ]);
    }
}
