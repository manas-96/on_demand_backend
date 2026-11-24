<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\ProviderEarning;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $providerA;

    private ProviderProfile $profileA;

    private User $providerB;

    private ProviderProfile $profileB;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerA = User::factory()->create(['role' => UserRole::Provider]);
        $this->profileA = ProviderProfile::create([
            'user_id' => $this->providerA->id,
            'company_name' => 'Provider A Services',
            'is_approved' => true,
        ]);

        $this->providerB = User::factory()->create(['role' => UserRole::Provider]);
        $this->profileB = ProviderProfile::create([
            'user_id' => $this->providerB->id,
            'company_name' => 'Provider B Services',
            'is_approved' => true,
        ]);

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
    }

    public function test_provider_can_access_provider_dashboard_and_menu_routes(): void
    {
        $response = $this->actingAs($this->providerA)
            ->get('/provider/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Technicians');
        $response->assertSee('Job Requests');
        $response->assertSee('Earnings');
        $response->assertViewHas('earningsTrend');
        $response->assertViewHas('bidStatusDistribution');
        $response->assertViewHas('jobStatusDistribution');
    }

    public function test_provider_can_view_own_technicians_and_add_new_technician(): void
    {
        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Alpha']);
        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'qualification' => 'Master Electrician',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get('/provider/technicians');

        $response->assertStatus(200);
        $response->assertSee('Tech Alpha');
        $response->assertSee('Master Electrician');

        // Add technician
        $addResponse = $this->actingAs($this->providerA)
            ->post('/provider/technicians', [
                'name' => 'Tech Beta',
                'email' => 'techbeta@example.com',
                'password' => 'password123',
                'phone' => '1234567890',
                'qualification' => 'HVAC Expert',
            ]);

        $addResponse->assertRedirect(route('provider.technicians.index'));
        $this->assertDatabaseHas('users', ['email' => 'techbeta@example.com', 'role' => UserRole::Technician->value]);
        $this->assertDatabaseHas('technician_profiles', ['provider_id' => $this->providerA->id, 'qualification' => 'HVAC Expert']);
    }

    public function test_provider_cannot_see_or_manage_technicians_of_another_provider(): void
    {
        $techBUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Provider B']);
        $techBProfile = TechnicianProfile::create([
            'user_id' => $techBUser->id,
            'provider_id' => $this->providerB->id,
            'provider_profile_id' => $this->profileB->id,
            'qualification' => 'Plumber B',
            'is_active' => true,
        ]);

        // Provider A views their own technicians list
        $response = $this->actingAs($this->providerA)
            ->get('/provider/technicians');

        $response->assertStatus(200);
        $response->assertDontSee('Tech Provider B');

        // Provider A attempts to toggle status of Provider B's technician
        $toggleResponse = $this->actingAs($this->providerA)
            ->post("/provider/technicians/{$techBProfile->id}/toggle-status");

        $toggleResponse->assertStatus(404);
    }

    public function test_provider_can_view_job_requests_belonging_to_them(): void
    {
        $category = ServiceCategory::create(['name' => 'Electrical', 'slug' => 'electrical', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Wiring', 'slug' => 'wiring', 'base_price' => 200, 'is_active' => true]);

        $srA = ServiceRequest::create([
            'request_number' => 'SR-PROV-A-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Electrical Short Circuit in Provider A area',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $reqA = ProviderJobRequest::create([
            'service_request_id' => $srA->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Sent,
            'sent_at' => now(),
        ]);

        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Assigned Tech Name']);
        JobAssignment::create([
            'service_request_id' => $srA->id,
            'provider_id' => $this->providerA->id,
            'technician_id' => $techUser->id,
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get('/provider/requests?tab=accepted');

        $response->assertStatus(200);
        $response->assertSee('SR-PROV-A-001');

        $showResponse = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$reqA->id}");

        $showResponse->assertStatus(200);
        $showResponse->assertSee('SR-PROV-A-001');
        $showResponse->assertSee('Assigned Tech Name');
    }

    public function test_provider_cannot_view_job_requests_belonging_to_another_provider(): void
    {
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Pipe Repair', 'slug' => 'pipe-repair', 'base_price' => 150, 'is_active' => true]);

        $srB = ServiceRequest::create([
            'request_number' => 'SR-PROV-B-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Pipe Burst for Provider B',
            'address_line' => '456 Elm St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $reqB = ProviderJobRequest::create([
            'service_request_id' => $srB->id,
            'provider_id' => $this->providerB->id,
            'provider_profile_id' => $this->profileB->id,
            'status' => ProviderJobRequestStatus::Sent,
            'sent_at' => now(),
        ]);

        // Provider A attempts to view Provider B's job request
        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$reqB->id}");

        $response->assertStatus(404);
    }

    public function test_provider_can_view_own_earnings_and_cannot_view_other_provider_earnings(): void
    {
        $category = ServiceCategory::create(['name' => 'Cleaning', 'slug' => 'cleaning', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Deep Clean', 'slug' => 'deep-clean', 'base_price' => 500, 'is_active' => true]);

        $sr = ServiceRequest::create([
            'request_number' => 'SR-EARN-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Deep cleaning',
            'address_line' => '789 Oak St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $invoiceA = Invoice::create([
            'invoice_number' => 'INV-001',
            'service_request_id' => $sr->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'subtotal' => 1000.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 1000.00,
            'status' => InvoiceStatus::Paid,
        ]);

        $invoiceB = Invoice::create([
            'invoice_number' => 'INV-002',
            'service_request_id' => $sr->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerB->id,
            'subtotal' => 5000.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 5000.00,
            'status' => InvoiceStatus::Paid,
        ]);

        ProviderEarning::create([
            'provider_id' => $this->providerA->id,
            'service_request_id' => $sr->id,
            'invoice_id' => $invoiceA->id,
            'gross_amount' => 1000.00,
            'platform_fee' => 100.00,
            'net_amount' => 900.00,
        ]);

        ProviderEarning::create([
            'provider_id' => $this->providerB->id,
            'service_request_id' => $sr->id,
            'invoice_id' => $invoiceB->id,
            'gross_amount' => 5000.00,
            'platform_fee' => 500.00,
            'net_amount' => 4500.00,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get('/provider/earnings');

        $response->assertStatus(200);
        $response->assertSee('900.00');
        $response->assertDontSee('4500.00');
    }

    public function test_provider_cannot_access_customer_list_or_admin_routes(): void
    {
        $response = $this->actingAs($this->providerA)
            ->get('/admin/customers');

        $response->assertStatus(403);
    }

    public function test_provider_can_filter_incoming_and_accepted_job_requests_by_status_and_search(): void
    {
        $category = ServiceCategory::create(['name' => 'Cleaning Services', 'slug' => 'cleaning-services', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'House Cleaning', 'slug' => 'house-cleaning', 'base_price' => 100, 'is_active' => true]);

        // 1. Awarded Request
        $srAwarded = ServiceRequest::create([
            'request_number' => 'SR-FILTER-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Deep house cleaning request',
            'address_line' => '100 Park Ave',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Awarded,
        ]);
        ProviderJobRequest::create([
            'service_request_id' => $srAwarded->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        // 2. Assigned Request
        $srAssigned = ServiceRequest::create([
            'request_number' => 'SR-FILTER-002',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Apartment cleaning request',
            'address_line' => '200 Elm St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Assigned,
        ]);
        ProviderJobRequest::create([
            'service_request_id' => $srAssigned->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        // 3. In Progress Request
        $srInProgress = ServiceRequest::create([
            'request_number' => 'SR-FILTER-003',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Office cleaning request',
            'address_line' => '300 Oak St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::InProgress,
        ]);
        ProviderJobRequest::create([
            'service_request_id' => $srInProgress->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        // Filter Accepted Jobs by status = assigned
        $resAssigned = $this->actingAs($this->providerA)
            ->get('/provider/requests/accepted?status=assigned');
        $resAssigned->assertStatus(200);
        $resAssigned->assertSee('SR-FILTER-002');
        $resAssigned->assertDontSee('SR-FILTER-001');
        $resAssigned->assertDontSee('SR-FILTER-003');

        // Filter Accepted Jobs by status = in_progress
        $resInProgress = $this->actingAs($this->providerA)
            ->get('/provider/requests/accepted?status=in_progress');
        $resInProgress->assertStatus(200);
        $resInProgress->assertSee('SR-FILTER-003');
        $resInProgress->assertDontSee('SR-FILTER-001');
        $resInProgress->assertDontSee('SR-FILTER-002');

        // Search Accepted Jobs by address line
        $resSearch = $this->actingAs($this->providerA)
            ->get('/provider/requests/accepted?search=100+Park');
        $resSearch->assertStatus(200);
        $resSearch->assertSee('SR-FILTER-001');
        $resSearch->assertDontSee('SR-FILTER-002');
        $resSearch->assertDontSee('SR-FILTER-003');
    }

    public function test_provider_request_details_displays_sub_category_under_category(): void
    {
        $parentCat = ServiceCategory::create(['name' => 'Cleaning Services', 'slug' => 'cleaning-services', 'is_active' => true]);
        $subCat = ServiceCategory::create(['name' => 'Deep Kitchen Clean', 'slug' => 'deep-kitchen-clean', 'parent_id' => $parentCat->id, 'is_active' => true]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-PROV-SUBCAT',
            'customer_id' => $this->customer->id,
            'service_category_id' => $parentCat->id,
            'sub_category_id' => $subCat->id,
            'request_type' => 'broadcast',
            'description' => 'Kitchen deep clean requested',
            'address_line' => '789 Elm Street',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Sent,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->providerA)->get("/provider/requests/{$jobRequest->id}");

        $response->assertStatus(200);
        $response->assertSee('Service Overview');
        $response->assertSee('Cleaning Services');
        $response->assertSee('Sub Category:');
        $response->assertSee('Deep Kitchen Clean');
    }

    public function test_provider_can_fetch_assigned_technician_live_location(): void
    {
        $tech = User::factory()->create([
            'role' => 'technician',
        ]);
        $techProfile = TechnicianProfile::create([
            'user_id' => $tech->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'is_active' => true,
            'is_approved' => true,
            'duty_status' => 'online',
            'current_latitude' => 12.9716,
            'current_longitude' => 77.5946,
            'last_location_at' => now(),
        ]);

        $category = ServiceCategory::create(['name' => 'Cleaning Services', 'slug' => 'cleaning-services']);

        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-20260925-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'description' => 'Test location request',
            'address_line' => '123 Main St',
            'latitude' => 12.9800,
            'longitude' => 77.6000,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Assigned,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'technician_id' => $tech->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $showResponse = $this->actingAs($this->providerA)->get("/provider/requests/{$jobRequest->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Technician Live Location');

        $locResponse = $this->actingAs($this->providerA)->get("/provider/requests/{$jobRequest->id}/technician-location");
        $locResponse->assertStatus(200);
        $locResponse->assertJson([
            'success' => true,
            'assigned' => true,
            'technician_name' => $tech->name,
            'latitude' => 12.9716,
            'longitude' => 77.5946,
        ]);
    }

    public function test_provider_can_download_final_invoice_pdf(): void
    {
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing']);
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-PROV-PDF-01',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'description' => 'Provider PDF download test',
            'address_line' => '456 Commercial St',
            'latitude' => 12.9800,
            'longitude' => 77.6000,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$jobRequest->id}/invoice/download");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_provider_job_details_shows_admin_commission_and_total_payable_when_paid(): void
    {
        $category = ServiceCategory::create(['name' => 'HVAC', 'slug' => 'hvac-provider-test']);
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-PROV-COMM-01',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'description' => 'Provider commission test',
            'address_line' => '789 Business Ave',
            'latitude' => 12.9800,
            'longitude' => 77.6000,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        Invoice::create([
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'invoice_number' => 'INV-PROV-COMM-01',
            'subtotal' => 500.00,
            'tax' => 25.00,
            'discount' => 0.00,
            'tip_amount' => 50.00,
            'total' => 575.00,
            'status' => InvoiceStatus::Paid,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$jobRequest->id}");

        $response->assertStatus(200);
        $response->assertSee('Payment & Settlement Overview', false);
        $response->assertSee('Admin Commission');
        $response->assertSee('Total Payable to You');
        $response->assertSee('Provider Net Settlement Breakdown');
    }

    public function test_provider_net_settlement_breakdown_with_quotation(): void
    {
        $this->providerA->providerProfile()->update(['commission' => 11.00]);

        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing-prov-test']);
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-PROV-COMM-02',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'description' => 'Provider commission quote test',
            'address_line' => '123 Test St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Completed,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        Quote::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'labor_subtotal' => 1000.00,
            'material_subtotal' => 1000.00,
            'tax' => 100.00,
            'total' => 2100.00,
            'status' => 'approved',
            'is_approved_by_provider' => true,
        ]);

        Invoice::create([
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $this->customer->id,
            'provider_id' => $this->providerA->id,
            'invoice_number' => 'INV-2000-TEST',
            'subtotal' => 1850.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'tip_amount' => 20.00,
            'total' => 1870.00,
            'status' => InvoiceStatus::Paid,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$jobRequest->id}");

        $response->assertStatus(200);
        $response->assertSee('$2,000.00'); // Service Cost
        $response->assertSee('$2,100.00'); // Grand Total
        $response->assertSee('-$220.00');  // Admin Commission (11% of $2000)
        $response->assertSee('$1,900.00'); // Total Payable to Provider
    }

    public function test_provider_request_details_displays_pre_work_and_post_work_photos_and_details(): void
    {
        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'request_number' => 'SR-PHOTOS-101',
            'status' => ServiceRequestStatus::InProgress,
            'description' => 'Fix leaking pipe and restore dry wall',
            'address_line' => '456 Oak Avenue',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferred_date' => now()->toDateString(),
        ]);

        $jobRequest = ProviderJobRequest::create([
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'service_request_id' => $serviceRequest->id,
            'status' => ProviderJobRequestStatus::Selected,
        ]);

        $techUser = User::factory()->create([
            'role' => UserRole::Technician,
            'name' => 'Alex MasterTech',
        ]);

        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'is_approved' => true,
            'is_active' => true,
        ]);

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'technician_id' => $techUser->id,
            'status' => JobAssignmentStatus::InProgress,
            'assigned_at' => now(),
        ]);

        JobInspection::create([
            'service_request_id' => $serviceRequest->id,
            'job_assignment_id' => $assignment->id,
            'technician_id' => $techUser->id,
            'technical_notes' => 'Found severe pipe corrosion behind main drywall',
            'estimated_labor_hours' => 2.50,
            'estimated_material_cost' => 150.00,
            'photos' => ['inspections/sample1.jpg', 'inspections/sample2.jpg'],
        ]);

        JobProgress::create([
            'service_request_id' => $serviceRequest->id,
            'technician_id' => $techUser->id,
            'progress_percentage' => 100,
            'description' => 'Replaced corroded PVC section successfully',
            'photos' => ['completion/finish1.jpg'],
        ]);

        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$jobRequest->id}");

        $response->assertStatus(200);
        $response->assertSee('Pre-Work Inspection Photos & Findings', false);
        $response->assertSee('Alex MasterTech');
        $response->assertSee('Found severe pipe corrosion behind main drywall');
        $response->assertSee('2.50 hrs');
        $response->assertSee('$150.00');
        $response->assertSee('storage/inspections/sample1.jpg');
        $response->assertSee('storage/inspections/sample2.jpg');
        $response->assertSee('Post-Work Proof Photos & Progress Updates', false);
        $response->assertSee('100% Completed');
        $response->assertSee('Replaced corroded PVC section successfully');
        $response->assertSee('storage/completion/finish1.jpg');
    }

    public function test_provider_can_send_chat_message_via_backend_endpoint(): void
    {
        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'request_number' => 'SR-CHAT-SEND-01',
            'status' => ServiceRequestStatus::Assigned,
            'description' => 'Chat test request',
            'address_line' => '789 Pine Road',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferred_date' => now()->toDateString(),
        ]);

        $jobRequest = ProviderJobRequest::create([
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'service_request_id' => $serviceRequest->id,
            'status' => ProviderJobRequestStatus::Selected,
        ]);

        // Authorized provider send via jobRequest id
        $response = $this->actingAs($this->providerA)
            ->postJson("/provider/requests/{$jobRequest->id}/chat-send", [
                'message' => 'Hello customer, I am on my way!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chat message sent successfully.',
            ]);

        // Authorized provider send via serviceRequest id
        $responseViaServiceId = $this->actingAs($this->providerA)
            ->postJson("/provider/requests/{$serviceRequest->id}/chat-send", [
                'message' => 'Follow up message using service request id',
            ]);

        $responseViaServiceId->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Chat message sent successfully.',
            ]);

        // Validation failure
        $emptyResponse = $this->actingAs($this->providerA)
            ->postJson("/provider/requests/{$jobRequest->id}/chat-send", [
                'message' => '',
            ]);

        $emptyResponse->assertStatus(422);

        // Unauthorized provider B cannot send on provider A's request
        $unauthorizedResponse = $this->actingAs($this->providerB)
            ->postJson("/provider/requests/{$jobRequest->id}/chat-send", [
                'message' => 'Unauthorized message attempt',
            ]);

        $unauthorizedResponse->assertStatus(404);
    }

    public function test_provider_can_view_chat_modal_even_without_technician_assignment(): void
    {
        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'request_number' => 'SR-CHAT-NOTECH-01',
            'status' => ServiceRequestStatus::Awarded,
            'description' => 'Accepted job without technician',
            'address_line' => '101 Oak Lane',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->addDays(2)->toDateString(),
        ]);

        $jobRequest = ProviderJobRequest::create([
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'service_request_id' => $serviceRequest->id,
            'status' => ProviderJobRequestStatus::Selected,
        ]);

        $response = $this->actingAs($this->providerA)
            ->get("/provider/requests/{$jobRequest->id}");

        $response->assertStatus(200);
        $response->assertSee('Customer Chat 💬');
        $response->assertSee('id="customer-chat-modal"', false);
        $response->assertSee('id="chat-send-form"', false);
    }

    public function test_provider_cannot_assign_technician_to_future_date_booking_in_web_panel(): void
    {
        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Dave']);
        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $futureDate = now()->addDays(3);
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-FUTURE-001',
            'customer_id' => $this->customer->id,
            'description' => 'Future installation job',
            'address_line' => '123 Future Way',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => $futureDate->toDateString(),
            'request_type' => 'broadcast',
            'status' => ServiceRequestStatus::Awarded,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        // Attempt assigning technician via Web Panel
        $response = $this->actingAs($this->providerA)
            ->post("/provider/requests/{$jobRequest->id}/assign-technician", [
                'technician_id' => $techUser->id,
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('locked until the scheduled booking date', session('error'));
        $this->assertDatabaseMissing('job_assignments', [
            'service_request_id' => $serviceRequest->id,
            'technician_id' => $techUser->id,
        ]);

        // Verify show page displays lock banner
        $showResponse = $this->actingAs($this->providerA)->get("/provider/requests/{$jobRequest->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Assignment Locked Until Scheduled Date');
    }

    public function test_provider_can_assign_technician_to_same_day_booking_in_web_panel(): void
    {
        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Dave']);
        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-TODAY-001',
            'customer_id' => $this->customer->id,
            'description' => 'Today emergency job',
            'address_line' => '456 Today St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => today()->toDateString(),
            'request_type' => 'broadcast',
            'status' => ServiceRequestStatus::Awarded,
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'status' => ProviderJobRequestStatus::Selected,
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($this->providerA)
            ->post("/provider/requests/{$jobRequest->id}/assign-technician", [
                'technician_id' => $techUser->id,
            ]);

        $response->assertSessionHas('success', 'Technician assigned successfully.');
        $this->assertDatabaseHas('job_assignments', [
            'service_request_id' => $serviceRequest->id,
            'technician_id' => $techUser->id,
            'status' => JobAssignmentStatus::Assigned,
        ]);
        $this->assertEquals(ServiceRequestStatus::Assigned, $serviceRequest->fresh()->status);
    }

    public function test_provider_api_cannot_assign_technician_to_future_date_booking(): void
    {
        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Dave']);
        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $futureDate = now()->addDays(5);
        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-FUTURE-API-001',
            'customer_id' => $this->customer->id,
            'description' => 'Future API job',
            'address_line' => '789 Future Ave',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => $futureDate->toDateString(),
            'request_type' => 'broadcast',
            'status' => ServiceRequestStatus::Awarded,
        ]);

        $response = $this->actingAs($this->providerA)
            ->postJson("/api/v1/provider/requests/{$serviceRequest->id}/assign-technician", [
                'technician_id' => $techUser->id,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'unlock_date' => $futureDate->toDateString(),
            ]);

        $this->assertDatabaseMissing('job_assignments', [
            'service_request_id' => $serviceRequest->id,
        ]);
    }

    public function test_provider_api_can_assign_technician_to_same_day_booking(): void
    {
        $techUser = User::factory()->create(['role' => UserRole::Technician, 'name' => 'Tech Dave']);
        TechnicianProfile::create([
            'user_id' => $techUser->id,
            'provider_id' => $this->providerA->id,
            'provider_profile_id' => $this->profileA->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => 'SR-TODAY-API-001',
            'customer_id' => $this->customer->id,
            'description' => 'Today API job',
            'address_line' => '789 Today Ave',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => today()->toDateString(),
            'request_type' => 'broadcast',
            'status' => ServiceRequestStatus::Awarded,
        ]);

        $response = $this->actingAs($this->providerA)
            ->postJson("/api/v1/provider/requests/{$serviceRequest->id}/assign-technician", [
                'technician_id' => $techUser->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Technician assigned successfully.',
            ]);

        $this->assertDatabaseHas('job_assignments', [
            'service_request_id' => $serviceRequest->id,
            'technician_id' => $techUser->id,
        ]);
    }

    public function test_provider_can_access_support_helpdesk_page(): void
    {
        $response = $this->actingAs($this->providerA)
            ->get('/provider/support');

        $response->assertStatus(200);
        $response->assertSee('Provider Support', false);
        $response->assertSee('Open New Ticket');
    }

    public function test_provider_can_access_support_live_chat_page(): void
    {
        $response = $this->actingAs($this->providerA)
            ->get('/provider/support/TKT-TEST12345');

        $response->assertStatus(200);
        $response->assertSee('Platform Support Desk Live Chat');
    }

    public function test_non_provider_cannot_access_provider_support(): void
    {
        $guestResponse = $this->get('/provider/support');
        $guestResponse->assertRedirect('/login');

        $customerResponse = $this->actingAs($this->customer)
            ->get('/provider/support');
        $customerResponse->assertStatus(403);
    }
}
