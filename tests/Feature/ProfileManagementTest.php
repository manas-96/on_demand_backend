<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $providerUser;

    protected ProviderProfile $providerProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
            'password' => Hash::make('password123'),
        ]);

        $this->providerUser = User::factory()->create([
            'role' => UserRole::Provider,
            'password' => Hash::make('password123'),
        ]);

        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->providerUser->id,
            'company_name' => 'Original Apex Services',
            'business_registration' => 'REG-112233',
            'address' => '100 Core Street',
            'service_radius_km' => 25.00,
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);
    }

    public function test_admin_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/profile');

        $response->assertStatus(200);
        $response->assertSee('Account Settings');
        $response->assertSee($this->admin->email);
    }

    public function test_admin_can_update_profile_information(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/profile', [
            'name' => 'Super Administrator',
            'email' => 'updatedadmin@example.com',
            'phone' => '+15550001111',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Super Administrator',
            'email' => 'updatedadmin@example.com',
            'phone' => '+15550001111',
        ]);
    }

    public function test_admin_can_change_password(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/profile/password', [
            'current_password' => 'password123',
            'password' => 'newsecretpassword123',
            'password_confirmation' => 'newsecretpassword123',
        ]);

        $response->assertRedirect('/admin/profile?tab=password');
        $this->assertTrue(Hash::check('newsecretpassword123', $this->admin->fresh()->password));
    }

    public function test_admin_cannot_change_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->admin)->put('/admin/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newsecretpassword123',
            'password_confirmation' => 'newsecretpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->admin->fresh()->password));
    }

    public function test_provider_can_view_profile_page(): void
    {
        $response = $this->actingAs($this->providerUser)->get('/provider/profile');

        $response->assertStatus(200);
        $response->assertSee('Provider Account');
        $response->assertSee($this->providerProfile->company_name);
    }

    public function test_provider_can_update_profile_and_company_information(): void
    {
        $response = $this->actingAs($this->providerUser)->put('/provider/profile', [
            'name' => 'Jane Provider Manager',
            'email' => 'janeprovider@example.com',
            'phone' => '+15559998888',
            'company_name' => 'Apex Maintenance Solutions LLC',
            'business_registration' => 'REG-998877-XP',
            'address' => '500 Skyline Drive, Suite 300',
            'service_radius_km' => 50.00,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->providerUser->id,
            'name' => 'Jane Provider Manager',
            'email' => 'janeprovider@example.com',
        ]);
        $this->assertDatabaseHas('provider_profiles', [
            'id' => $this->providerProfile->id,
            'company_name' => 'Apex Maintenance Solutions LLC',
            'business_registration' => 'REG-998877-XP',
            'address' => '500 Skyline Drive, Suite 300',
            'service_radius_km' => 50.00,
        ]);
    }

    public function test_provider_can_change_password(): void
    {
        $response = $this->actingAs($this->providerUser)->put('/provider/profile/password', [
            'current_password' => 'password123',
            'password' => 'newproviderpass123',
            'password_confirmation' => 'newproviderpass123',
        ]);

        $response->assertRedirect('/provider/profile?tab=password');
        $this->assertTrue(Hash::check('newproviderpass123', $this->providerUser->fresh()->password));
    }

    public function test_provider_cannot_change_password_with_invalid_current_password(): void
    {
        $response = $this->actingAs($this->providerUser)->put('/provider/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newproviderpass123',
            'password_confirmation' => 'newproviderpass123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->providerUser->fresh()->password));
    }
}
