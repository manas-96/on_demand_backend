<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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

        $response = $this->post('/login', [
            'email' => 'provider@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/provider');

        $dashboardResponse = $this->actingAs($provider)->get('/provider');
        $dashboardResponse->assertRedirect('/provider/dashboard');
    }
}
