<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRequestApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'icon' => 'plumbing',
            'is_active' => true,
        ]);
    }

    public function test_customer_can_fetch_their_bookings(): void
    {
        ServiceRequest::create([
            'request_number' => 'SR-20260915-0001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Fix leaky sink',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/customer/requests');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Service requests retrieved successfully.',
            ])
            ->assertJsonFragment([
                'request_number' => 'SR-20260915-0001',
                'description' => 'Fix leaky sink',
            ]);
    }

    public function test_customer_can_filter_bookings_by_active_status(): void
    {
        // Active request
        ServiceRequest::create([
            'request_number' => 'SR-ACTIVE-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Active plumbing job',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::InProgress,
        ]);

        // Completed request
        ServiceRequest::create([
            'request_number' => 'SR-DONE-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Completed electrical job',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/customer/requests?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['request_number' => 'SR-ACTIVE-001'])
            ->assertJsonMissing(['request_number' => 'SR-DONE-001']);
    }

    public function test_customer_can_filter_bookings_by_completed_status(): void
    {
        // Active request
        ServiceRequest::create([
            'request_number' => 'SR-ACTIVE-002',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Active plumbing job',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        // Completed request
        ServiceRequest::create([
            'request_number' => 'SR-DONE-002',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Completed job',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/customer/requests?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['request_number' => 'SR-DONE-002'])
            ->assertJsonMissing(['request_number' => 'SR-ACTIVE-002']);
    }

    public function test_customer_can_download_invoice_pdf(): void
    {
        $request = ServiceRequest::create([
            'request_number' => 'SR-PDF-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'PDF Invoice Test',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->get("/api/v1/customer/requests/{$request->id}/invoice/download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_authenticated_user_can_create_service_request(): void
    {
        $technicianUser = User::factory()->create([
            'role' => UserRole::Technician,
        ]);

        $payload = [
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Fix leaking pipe in bathroom',
            'address_line' => '456 Tech Lane',
            'latitude' => 23.5204,
            'longitude' => 87.3119,
            'preferred_date' => now()->addDay()->toDateString(),
            'preferred_start_time' => '10:00',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($technicianUser, 'sanctum')
            ->postJson('/api/v1/customer/requests', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Service request created. Pay the booking deposit to confirm it.',
            ])
            ->assertJsonPath('data.customer_id', $technicianUser->id)
            ->assertJsonPath('data.status', ServiceRequestStatus::PendingPayment->value);

        $this->assertDatabaseHas('service_requests', [
            'customer_id' => $technicianUser->id,
            'description' => 'Fix leaking pipe in bathroom',
        ]);
    }

    public function test_awarded_service_request_resolves_provider_and_company_name(): void
    {
        $providerUser = User::factory()->create([
            'name' => 'John Provider',
            'role' => UserRole::Provider,
        ]);
        $profile = ProviderProfile::create([
            'user_id' => $providerUser->id,
            'company_name' => 'Acme Services',
            'business_type' => 'company',
            'is_verified' => true,
        ]);

        $sr = ServiceRequest::create([
            'request_number' => 'SR-AWARDED-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Awarded Request Test',
            'address_line' => '789 Elm St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Awarded,
        ]);

        ProviderJobRequest::create([
            'service_request_id' => $sr->id,
            'provider_id' => $providerUser->id,
            'provider_profile_id' => $profile->id,
            'status' => 'selected',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$sr->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'awarded')
            ->assertJsonPath('data.provider_id', $providerUser->id)
            ->assertJsonPath('data.provider_name', 'John Provider')
            ->assertJsonPath('data.company_name', 'Acme Services')
            ->assertJsonPath('data.provider_rating', null)
            ->assertJsonPath('data.provider_completed_jobs', '0 jobs')
            ->assertJsonPath('data.technician_name', null);
    }
}
