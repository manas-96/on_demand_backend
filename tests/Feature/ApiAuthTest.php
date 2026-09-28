<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'phone' => '+15551112222',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'role' => 'customer',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email', 'role', 'phone'],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => UserRole::Customer->value,
        ]);
    }

    public function test_user_can_login_via_api(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.user.email', 'user@example.com');
        $this->assertNotNull($response->json('data.token'));
    }

    public function test_deactivated_user_cannot_login_via_api(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Customer,
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_fetch_profile_via_api(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_logout_via_api(): void
    {
        $user = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }
}
