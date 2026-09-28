<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected User $providerUser;

    protected ProviderProfile $providerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->providerUser = User::factory()->create(['role' => UserRole::Provider]);

        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->providerUser->id,
            'company_name' => 'Test Provider Co',
            'is_approved' => false,
            'is_accepting_jobs' => true,
        ]);
    }

    public function test_non_admin_user_is_blocked_from_admin_panel(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/dashboard');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Executive Control Center');
    }

    public function test_admin_can_toggle_customer_active_status(): void
    {
        $this->assertTrue($this->customer->is_active);

        $response = $this->actingAs($this->admin)->post("/admin/customers/{$this->customer->id}/toggle-status");
        $response->assertRedirect();

        $this->assertFalse($this->customer->fresh()->is_active);
    }

    public function test_admin_can_access_customer_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/customers/create');
        $response->assertStatus(200);
        $response->assertSee('Register New Customer');
    }

    public function test_admin_can_create_new_customer(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/customers', [
            'name' => 'Alice Cooper',
            'email' => 'alice@example.com',
            'phone' => '+15558887777',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address_line' => '101 Maple Street, Apartment 4B',
        ]);

        $response->assertRedirect('/admin/customers');
        $this->assertDatabaseHas('users', ['email' => 'alice@example.com', 'role' => UserRole::Customer]);
        $this->assertDatabaseHas('customer_addresses', [
            'address_line' => '101 Maple Street, Apartment 4B',
        ]);
    }

    public function test_admin_can_toggle_provider_approval(): void
    {
        $this->assertFalse($this->providerProfile->is_approved);

        $response = $this->actingAs($this->admin)->post("/admin/providers/{$this->providerProfile->id}/toggle-approval");
        $response->assertRedirect();

        $this->assertTrue($this->providerProfile->fresh()->is_approved);
    }

    public function test_admin_sees_full_provider_address_on_index_and_show_pages(): void
    {
        $this->providerProfile->update([
            'address' => '742 Evergreen Terrace, Sector 12',
        ]);

        $indexResponse = $this->actingAs($this->admin)->get('/admin/providers');
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('742 Evergreen Terrace, Sector 12');
        $indexResponse->assertSee('provider-map');
        $indexResponse->assertSee('Provider Coverage');
        $indexResponse->assertSee('Location Map');

        $showResponse = $this->actingAs($this->admin)->get("/admin/providers/{$this->providerProfile->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertSee('742 Evergreen Terrace, Sector 12');
        $showResponse->assertDontSee('Latitude:');
    }

    public function test_admin_can_access_provider_create_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/providers/create');
        $response->assertStatus(200);
        $response->assertSee('Register New Service Provider');
    }

    public function test_admin_can_create_new_provider(): void
    {
        $category = ServiceCategory::create(['name' => 'Roofing Services', 'slug' => 'roofing-services', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Roof Repair', 'slug' => 'roof-repair', 'base_price' => 500.00]);

        $response = $this->actingAs($this->admin)->post('/admin/providers', [
            'company_name' => 'Metro Plumbing Solutions',
            'business_registration' => 'REG-998877',
            'address' => '500 Tech Boulevard, Suite 200',
            'service_radius_km' => 30.5,
            'commission' => 15.50,
            'name' => 'Michael Scott',
            'email' => 'michael@metroplumbing.com',
            'phone' => '+15559876543',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'categories' => [$category->id],
        ]);

        $response->assertRedirect('/admin/providers');
        $this->assertDatabaseHas('users', ['email' => 'michael@metroplumbing.com', 'role' => UserRole::Provider]);
        $this->assertDatabaseHas('provider_profiles', [
            'company_name' => 'Metro Plumbing Solutions',
            'address' => '500 Tech Boulevard, Suite 200',
            'commission' => 15.50,
            'is_approved' => true,
        ]);
        $this->assertDatabaseHas('provider_services', [
            'service_id' => $service->id,
        ]);
    }

    public function test_admin_can_access_provider_edit_form(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/providers/{$this->providerProfile->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('Edit Service Provider');
        $response->assertSee($this->providerProfile->company_name);
    }

    public function test_admin_can_update_provider(): void
    {
        $category = ServiceCategory::create(['name' => 'Gardening', 'slug' => 'gardening', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Lawn Mowing', 'slug' => 'lawn-mowing', 'base_price' => 200.00]);

        $response = $this->actingAs($this->admin)->put("/admin/providers/{$this->providerProfile->id}", [
            'company_name' => 'Updated Company Name Inc',
            'business_registration' => 'REG-UPDATED-123',
            'address' => '888 Innovation Highway',
            'service_radius_km' => 45.0,
            'commission' => 20.00,
            'name' => 'Updated Contact Name',
            'email' => $this->providerUser->email,
            'phone' => '+15550009999',
            'categories' => [$category->id],
        ]);

        $response->assertRedirect('/admin/providers');
        $this->assertDatabaseHas('provider_profiles', [
            'id' => $this->providerProfile->id,
            'company_name' => 'Updated Company Name Inc',
            'address' => '888 Innovation Highway',
            'commission' => 20.00,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->providerUser->id,
            'name' => 'Updated Contact Name',
            'phone' => '+15550009999',
        ]);
        $this->assertDatabaseHas('provider_services', [
            'provider_profile_id' => $this->providerProfile->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_admin_can_create_new_service_category(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/services', [
            'name' => 'Carpentry',
            'image_url' => 'https://example.com/carpentry.jpg',
        ]);

        $response->assertRedirect('/admin/services');
        $this->assertDatabaseHas('service_categories', [
            'name' => 'Carpentry',
            'slug' => 'carpentry',
            'image' => 'https://example.com/carpentry.jpg',
        ]);
    }

    public function test_admin_can_override_request_status_with_audit_history(): void
    {
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Leak Fix', 'slug' => 'leak-fix', 'base_price' => 500.00]);

        $request = ServiceRequest::create([
            'request_number' => 'SR-OVERRIDE-01',
            'customer_id' => $this->customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/requests/{$request->id}/override-status", [
            'new_status' => 'completed',
            'reason' => 'Customer requested manual completion confirmation',
        ]);

        $response->assertRedirect();
        $this->assertEquals(ServiceRequestStatus::Completed, $request->fresh()->status);
        $this->assertDatabaseHas('job_status_histories', [
            'service_request_id' => $request->id,
            'changed_by_type' => 'admin',
            'new_status' => 'completed',
        ]);
    }

    public function test_admin_request_details_displays_category_and_sub_category(): void
    {
        $parentCat = ServiceCategory::create(['name' => 'Home Maintenance', 'slug' => 'home-maintenance', 'is_active' => true]);
        $subCat = ServiceCategory::create(['name' => 'Pipe Repair', 'slug' => 'pipe-repair', 'parent_id' => $parentCat->id, 'is_active' => true]);

        $request = ServiceRequest::create([
            'request_number' => 'SR-SUBCAT-TEST',
            'customer_id' => $this->customer->id,
            'service_category_id' => $parentCat->id,
            'sub_category_id' => $subCat->id,
            'request_type' => 'broadcast',
            'description' => 'Fix leaking pipe in bathroom',
            'address_line' => '456 Oak Lane',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/requests/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('Customer Problem Requirements');
        $response->assertSee('Home Maintenance');
        $response->assertSee('Pipe Repair');
    }
}
