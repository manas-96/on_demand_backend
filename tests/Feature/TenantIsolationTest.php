<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_a_cannot_access_customer_b_service_request(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing-test',
            'is_active' => true,
        ]);

        $service = Service::create([
            'service_category_id' => $category->id,
            'name' => 'Leak Fix',
            'slug' => 'leak-fix-test',
            'base_price' => 500.00,
            'is_active' => true,
        ]);

        $customerA = User::factory()->create(['role' => UserRole::Customer]);
        $customerB = User::factory()->create(['role' => UserRole::Customer]);

        $requestB = ServiceRequest::create([
            'request_number' => 'SR-CUST-B-001',
            'customer_id' => $customerB->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Customer B Private Request',
            'address_line' => '456 Side St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        // Customer A attempts to view Customer B's request
        $response = $this->actingAs($customerA, 'sanctum')
            ->getJson("/api/v1/customer/requests/{$requestB->id}");

        $response->assertStatus(404);
    }

    public function test_technician_cannot_access_provider_financial_earnings(): void
    {
        $technician = User::factory()->create(['role' => UserRole::Technician]);

        $response = $this->actingAs($technician, 'sanctum')
            ->getJson('/api/v1/provider/earnings');

        $response->assertStatus(403);
    }
}
