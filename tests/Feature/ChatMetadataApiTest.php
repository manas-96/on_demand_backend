<?php

namespace Tests\Feature;

use App\Enums\JobAssignmentStatus;
use App\Enums\QuoteStatus;
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

class ChatMetadataApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $providerUser;

    protected ProviderProfile $providerProfile;

    protected User $techUser;

    protected TechnicianProfile $techProfile;

    protected ServiceCategory $category;

    protected ServiceRequest $serviceRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
            'phone' => '+15550001111',
        ]);

        $this->providerUser = User::factory()->create([
            'role' => UserRole::Provider,
            'phone' => '+15550002222',
            'name' => 'Acme Services',
        ]);

        $this->techUser = User::factory()->create([
            'role' => UserRole::Technician,
            'phone' => '+15550003333',
            'name' => 'John Technician',
        ]);

        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->providerUser->id,
            'company_name' => 'Acme Services',
            'is_approved' => true,
        ]);

        $this->techProfile = TechnicianProfile::create([
            'user_id' => $this->techUser->id,
            'provider_id' => $this->providerUser->id,
            'provider_profile_id' => $this->providerProfile->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
        ]);

        $this->serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-CHAT-TEST-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaky pipe under kitchen sink',
            'address_line' => '456 Elm St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);
    }

    public function test_customer_cannot_chat_with_technician_or_provider_before_assignment_and_quote_approval(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$this->serviceRequest->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_chat_technician' => false,
                    'can_chat_provider' => false,
                ],
            ]);
    }

    public function test_customer_can_chat_with_technician_when_job_is_assigned(): void
    {
        JobAssignment::create([
            'service_request_id' => $this->serviceRequest->id,
            'provider_id' => $this->providerUser->id,
            'technician_id' => $this->techUser->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $this->serviceRequest->update(['status' => ServiceRequestStatus::Assigned]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$this->serviceRequest->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_chat_technician' => true,
                    'technician_id' => $this->techUser->id,
                    'technician_name' => 'John Technician',
                    'technician_phone' => '+15550003333',
                ],
            ]);
    }

    public function test_customer_can_chat_with_provider_when_quote_is_approved_by_provider(): void
    {
        Quote::create([
            'service_request_id' => $this->serviceRequest->id,
            'provider_id' => $this->providerUser->id,
            'technician_id' => $this->techUser->id,
            'labor_subtotal' => 100.00,
            'material_subtotal' => 50.00,
            'tax' => 15.00,
            'discount' => 0.00,
            'total' => 165.00,
            'status' => QuoteStatus::Approved,
            'is_approved_by_provider' => true,
            'provider_approved_at' => now(),
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$this->serviceRequest->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_chat_provider' => true,
                    'provider_id' => $this->providerUser->id,
                    'provider_name' => 'Acme Services',
                    'provider_phone' => '+15550002222',
                ],
            ]);
    }

    public function test_technician_receives_customer_details_and_chat_flag_for_assigned_job(): void
    {
        $assignment = JobAssignment::create([
            'service_request_id' => $this->serviceRequest->id,
            'provider_id' => $this->providerUser->id,
            'technician_id' => $this->techUser->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Arrived,
        ]);

        $response = $this->actingAs($this->techUser, 'sanctum')
            ->getJson("/api/v1/technician/jobs/{$assignment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_chat_customer' => true,
                    'service_request' => [
                        'customer' => [
                            'id' => $this->customer->id,
                            'phone' => '+15550001111',
                        ],
                    ],
                ],
            ]);
    }
}
