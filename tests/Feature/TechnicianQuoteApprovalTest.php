<?php

namespace Tests\Feature;

use App\Enums\JobAssignmentStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\JobAssignment;
use App\Models\ProviderProfile;
use App\Models\Quote;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnicianQuoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $providerUser;

    protected ProviderProfile $providerProfile;

    protected User $techUser;

    protected TechnicianProfile $techProfile;

    protected ServiceRequest $serviceRequest;

    protected JobAssignment $jobAssignment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->providerUser = User::factory()->create(['role' => UserRole::Provider]);
        $this->techUser = User::factory()->create(['role' => UserRole::Technician]);

        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->providerUser->id,
            'company_name' => 'Alpha Heating & AC',
            'is_approved' => true,
        ]);

        $this->techProfile = TechnicianProfile::create([
            'user_id' => $this->techUser->id,
            'provider_id' => $this->providerUser->id,
            'provider_profile_id' => $this->providerProfile->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $category = ServiceCategory::create(['name' => 'HVAC', 'slug' => 'hvac', 'is_active' => true]);

        $this->serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-QUOTE-FLOW-01',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'request_type' => 'broadcast',
            'description' => 'AC unit replacement inspection',
            'address_line' => '100 Tech Blvd',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Assigned,
        ]);

        $this->jobAssignment = JobAssignment::create([
            'service_request_id' => $this->serviceRequest->id,
            'provider_id' => $this->providerUser->id,
            'technician_id' => $this->techUser->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Arrived,
        ]);
    }

    public function test_technician_quote_requires_provider_approval_before_customer_visibility(): void
    {
        // 1. Technician uploads quote
        $uploadResponse = $this->actingAs($this->techUser)->postJson("/api/v1/technician/jobs/{$this->jobAssignment->id}/quote", [
            'items' => [
                ['description' => 'Compressor diagnostic & replacement', 'type' => 'labor', 'quantity' => 1, 'unit_price' => 250.00],
                ['description' => 'R410A Refrigerant 5kg', 'type' => 'material', 'quantity' => 1, 'unit_price' => 120.00],
            ],
            'tax_rate' => 0.05,
            'notes' => 'High priority compressor repair',
        ]);

        $uploadResponse->assertStatus(201);
        $quoteId = $uploadResponse->json('data.id');
        $quote = Quote::find($quoteId);

        $this->assertFalse($quote->is_approved_by_provider);
        $this->assertNull($quote->provider_approved_at);

        // 2. Customer attempts to view quote - should be hidden (404)
        $custViewResponse = $this->actingAs($this->customer)->getJson("/api/v1/customer/requests/{$this->serviceRequest->id}/quote");
        $custViewResponse->assertStatus(404);

        // 3. Provider approves quotation
        $approveResponse = $this->actingAs($this->providerUser)->postJson("/api/v1/provider/quotes/{$quoteId}/approve");
        $approveResponse->assertStatus(200);

        $quote->refresh();
        $this->assertTrue($quote->is_approved_by_provider);
        $this->assertNotNull($quote->provider_approved_at);

        // 4. Customer views quote now - should be returned successfully (200)
        $custViewResponse2 = $this->actingAs($this->customer)->getJson("/api/v1/customer/requests/{$this->serviceRequest->id}/quote");
        $custViewResponse2->assertStatus(200);
        $custViewResponse2->assertJsonPath('data.id', $quoteId);
        $custViewResponse2->assertJsonPath('data.is_approved_by_provider', true);

        // 5. Customer approves post-inspection quote
        $custApproveResponse = $this->actingAs($this->customer)->postJson("/api/v1/customer/quotes/{$quoteId}/approve");
        $custApproveResponse->assertStatus(200);
        $this->assertEquals('approved', $quote->fresh()->status->value);
    }

    public function test_provider_can_edit_technician_quote_and_approve_it(): void
    {
        // 1. Technician submits quote
        $uploadResponse = $this->actingAs($this->techUser)->postJson("/api/v1/technician/jobs/{$this->jobAssignment->id}/quote", [
            'items' => [
                ['description' => 'Original Labor', 'type' => 'labor', 'quantity' => 1, 'unit_price' => 100.00],
            ],
            'tax_rate' => 0.05,
        ]);
        $uploadResponse->assertStatus(201);
        $quoteId = $uploadResponse->json('data.id');

        // 2. Provider edits quote items and approves it via Web route
        $editResponse = $this->actingAs($this->providerUser)->put("/provider/quotes/{$quoteId}", [
            'items' => [
                ['description' => 'Revised Labor Item', 'type' => 'labor', 'quantity' => 2, 'unit_price' => 150.00],
                ['description' => 'New Material Item', 'type' => 'material', 'quantity' => 1, 'unit_price' => 50.00],
            ],
            'tax_rate' => 0.05,
            'discount' => 10.00,
            'notes' => 'Updated by provider with discount',
            'approve' => '1',
        ]);

        $editResponse->assertRedirect();

        $quote = Quote::with('items')->find($quoteId);
        $this->assertTrue($quote->is_approved_by_provider);
        $this->assertCount(2, $quote->items);
        $this->assertEquals(300.00, $quote->labor_subtotal);
        $this->assertEquals(50.00, $quote->material_subtotal);
        // Subtotal = 350, Tax 5% = 17.50, Discount = 10 -> Total = 357.50
        $this->assertEquals(17.50, $quote->tax);
        $this->assertEquals(357.50, $quote->total);
        $this->assertEquals('Updated by provider with discount', $quote->notes);
    }

    public function test_technician_completing_job_updates_service_request_status_to_completed(): void
    {
        $response = $this->actingAs($this->techUser)
            ->postJson("/api/v1/technician/jobs/{$this->jobAssignment->id}/complete");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(JobAssignmentStatus::Completed, $this->jobAssignment->fresh()->status);
        $this->assertEquals(ServiceRequestStatus::Completed, $this->serviceRequest->fresh()->status);
    }
}
