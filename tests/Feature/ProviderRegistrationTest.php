<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_public_provider_registration_page(): void
    {
        $response = $this->get('/register/provider');
        $response->assertStatus(200);
        $response->assertSee('Register as a Service Provider');
    }

    public function test_provider_can_self_register_and_is_authenticated(): void
    {
        $category = ServiceCategory::create(['name' => 'HVAC Repair', 'slug' => 'hvac-repair', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'AC Cleaning', 'slug' => 'ac-cleaning', 'base_price' => 300.00]);

        $response = $this->post('/register/provider', [
            'company_name' => 'Breeze Air Services',
            'business_registration' => 'REG-554433',
            'address' => '100 Windmill Lane, Sector 8',
            'service_radius_km' => 35.0,
            'name' => 'David Breeze',
            'email' => 'david@breezeair.com',
            'phone' => '+15551112222',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'categories' => [$category->id],
        ]);

        $response->assertRedirect('/provider/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'david@breezeair.com',
            'role' => UserRole::Provider,
        ]);

        $this->assertDatabaseHas('provider_profiles', [
            'company_name' => 'Breeze Air Services',
            'address' => '100 Windmill Lane, Sector 8',
        ]);

        $this->assertDatabaseHas('provider_services', [
            'service_id' => $service->id,
        ]);
    }
}
