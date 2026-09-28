<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
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

    public function test_customer_can_pay_for_service_request(): void
    {
        $request = ServiceRequest::create([
            'request_number' => 'SR-PAY-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Payment Test',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$request->id}/pay", [
                'payment_method' => 'card',
                'amount' => 150.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment processed and settled successfully.',
                'data' => [
                    'is_paid' => true,
                ],
            ]);
    }
}
