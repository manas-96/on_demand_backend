<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Sign In to Portal');
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => UserRole::Admin,
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');

        $dashboardResponse = $this->actingAs($admin)->get('/admin');
        $dashboardResponse->assertRedirect('/admin/dashboard');
    }

    public function test_provider_login_redirects_to_provider_dashboard(): void
    {
        $provider = User::factory()->create([
            'email' => 'provider@example.com',
            'role' => UserRole::Provider,
        ]);
        ProviderProfile::create([
            'user_id' => $provider->id,
            'company_name' => 'Approved Co',
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'provider@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/provider');

        $dashboardResponse = $this->actingAs($provider)->get('/provider');
        $dashboardResponse->assertRedirect('/provider/dashboard');
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'off@example.com', 'role' => UserRole::Admin, 'is_active' => false]);

        $this->post('/login', ['email' => 'off@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Your account has been deactivated. Please contact support.']);

        $this->assertGuest();
    }

    public function test_customers_and_technicians_are_refused_and_signed_out(): void
    {
        foreach ([UserRole::Customer, UserRole::Technician] as $role) {
            User::factory()->create(['email' => "{$role->value}@example.com", 'role' => $role]);

            $this->post('/login', ['email' => "{$role->value}@example.com", 'password' => 'password'])
                ->assertSessionHasErrors('email');

            $this->assertGuest();
        }
    }

    public function test_user_deactivated_mid_session_is_signed_out_of_the_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $admin->update(['is_active' => false]);

        $this->actingAs($admin->fresh())->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'role' => UserRole::Admin]);

        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
