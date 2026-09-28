<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRequestDateFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->category = ServiceCategory::create(['name' => 'HVAC', 'slug' => 'hvac', 'is_active' => true]);
    }

    public function test_admin_can_filter_requests_by_date_range(): void
    {
        // 1. Create old request (10 days ago)
        $oldRequest = ServiceRequest::create([
            'request_number' => 'SR-OLD-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Old repair job',
            'address_line' => '100 Old St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->subDays(10)->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);
        DB::table('service_requests')->where('id', $oldRequest->id)->update([
            'created_at' => now()->subDays(10),
        ]);

        // 2. Create today's request
        $todayRequest = ServiceRequest::create([
            'request_number' => 'SR-TODAY-002',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Today repair job',
            'address_line' => '200 New St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
            'created_at' => now(),
        ]);

        // Test custom range: from today to today
        $response = $this->actingAs($this->admin)->get('/admin/requests?from_date='.now()->toDateString().'&to_date='.now()->toDateString());
        $response->assertStatus(200);
        $response->assertSee('SR-TODAY-002');
        $response->assertDontSee('SR-OLD-001');

        // Test quick preset: today
        $presetResponse = $this->actingAs($this->admin)->get('/admin/requests?date_preset=today');
        $presetResponse->assertStatus(200);
        $presetResponse->assertSee('SR-TODAY-002');
        $presetResponse->assertDontSee('SR-OLD-001');
    }

    public function test_admin_can_filter_by_preferred_service_date(): void
    {
        $req1 = ServiceRequest::create([
            'request_number' => 'SR-PREF-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Future preferred date',
            'address_line' => '300 Future St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->addDays(5)->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
            'created_at' => now(),
        ]);

        $futureDateStr = now()->addDays(5)->toDateString();
        $response = $this->actingAs($this->admin)->get("/admin/requests?date_field=preferred_date&from_date={$futureDateStr}&to_date={$futureDateStr}");
        $response->assertStatus(200);
        $response->assertSee('SR-PREF-001');
    }

    public function test_admin_can_view_request_details_page_with_assignment_and_invoice(): void
    {
        $provider = User::factory()->create(['role' => UserRole::Provider]);
        $technician = User::factory()->create(['role' => UserRole::Technician]);

        $request = ServiceRequest::create([
            'request_number' => 'SR-ASSIGN-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Detailed test job',
            'address_line' => '400 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        JobAssignment::create([
            'service_request_id' => $request->id,
            'provider_id' => $provider->id,
            'technician_id' => $technician->id,
            'status' => JobAssignmentStatus::Assigned,
            'assigned_at' => now(),
        ]);

        Invoice::create([
            'service_request_id' => $request->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $provider->id,
            'invoice_number' => 'INV-TEST-001',
            'subtotal' => 200.00,
            'tax' => 10.00,
            'discount' => 0.00,
            'tip_amount' => 25.00,
            'total' => 235.00,
            'status' => InvoiceStatus::Paid,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/requests/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('SR-ASSIGN-001');
        $response->assertSee('Download Invoice 📄');
        $response->assertSee('Admin Financial Profit Breakdown 📈');
        $response->assertSee('Provider & Technician Details', false);
    }
}
