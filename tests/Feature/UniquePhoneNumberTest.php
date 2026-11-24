<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UniquePhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $providerUser;

    protected ProviderProfile $providerProfile;

    protected User $existingUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->providerUser = User::factory()->create(['role' => UserRole::Provider]);
        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->providerUser->id,
            'company_name' => 'Existing Provider Co',
            'is_approved' => true,
        ]);

        $this->existingUser = User::factory()->create([
            'role' => UserRole::Customer,
            'phone' => '+15559990000',
        ]);
    }

    public function test_api_customer_registration_fails_when_phone_is_already_taken(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'phone' => '+15559990000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function test_web_provider_registration_fails_when_phone_is_already_taken_by_a_provider(): void
    {
        User::factory()->create(['role' => UserRole::Provider, 'phone' => '+15558880000']);

        $this->post('/register/provider', $this->providerSignup('+15558880000'))->assertSessionHasErrors(['phone']);
    }

    public function test_web_provider_registration_may_reuse_a_customers_phone(): void
    {
        // Same person can hold a customer and a provider account (one per role).
        $this->post('/register/provider', $this->providerSignup('+15559990000'))->assertSessionDoesntHaveErrors(['phone']);
    }

    public function test_admin_creating_customer_fails_when_phone_is_already_taken(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/customers', [
            'name' => 'Admin Customer',
            'email' => 'admin_customer@example.com',
            'phone' => '+15559990000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_provider_adding_technician_fails_when_phone_is_already_taken_by_a_technician(): void
    {
        User::factory()->create(['role' => UserRole::Technician, 'phone' => '+15557770000']);

        $this->actingAs($this->providerUser)->post('/provider/technicians', [
            'name' => 'New Tech',
            'email' => 'tech_new@example.com',
            'phone' => '+15557770000',
            'password' => 'password123',
        ])->assertSessionHasErrors(['phone']);
    }

    public function test_provider_adding_technician_may_reuse_a_customers_phone(): void
    {
        $this->actingAs($this->providerUser)->post('/provider/technicians', [
            'name' => 'New Tech',
            'email' => 'tech_new@example.com',
            'phone' => '+15559990000',
            'password' => 'password123',
        ])->assertSessionDoesntHaveErrors(['phone']);
    }

    public function test_user_can_update_profile_keeping_same_phone_number(): void
    {
        $response = $this->actingAs($this->existingUser)->post('/admin/profile', [
            'name' => $this->existingUser->name,
            'email' => $this->existingUser->email,
            'phone' => '+15559990000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('+15559990000', $this->existingUser->fresh()->phone);
    }

    /**
     * @return array<string, mixed>
     */
    private function providerSignup(string $phone): array
    {
        return [
            'company_name' => 'Duplicate Phone Care',
            'address' => '123 Main St',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'service_radius_km' => 25,
            'name' => 'Provider Owner',
            'email' => 'dup_provider@example.com',
            'phone' => $phone,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'categories' => [ServiceCategory::create(['name' => 'Care', 'slug' => 'care-'.uniqid(), 'is_active' => true])->id],
        ];
    }
}
