<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api_and_receives_otp(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Customer',
            'email' => 'john@example.com',
            'phone' => '+15551112222',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'role' => 'customer',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'email' => 'john@example.com',
                'requires_verification' => true,
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => UserRole::Customer->value,
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('email_otps', [
            'email' => 'john@example.com',
            'attempts' => 0,
        ]);

        Notification::assertSentTo(
            User::where('email', 'john@example.com')->first(),
            VerifyEmailOtpNotification::class
        );
    }

    public function test_user_can_verify_email_otp_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        EmailOtp::create([
            'email' => 'john@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email-otp', [
            'email' => 'john@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email', 'email_verified_at'],
            ],
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_otps', ['email' => 'john@example.com']);
    }

    public function test_user_cannot_verify_with_invalid_otp(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $otpRecord = EmailOtp::create([
            'email' => 'john@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email-otp', [
            'email' => 'john@example.com',
            'otp' => '999999',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, $otpRecord->fresh()->attempts);
    }

    public function test_user_cannot_verify_with_expired_otp(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        EmailOtp::create([
            'email' => 'john@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email-otp', [
            'email' => 'john@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_resend_email_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/resend-email-otp', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    }

    public function test_resend_email_otp_respects_cooldown(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        EmailOtp::create([
            'email' => 'john@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/resend-email-otp', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_customer_login_requires_otp_and_sends_notification(): void
    {
        Notification::fake();

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
        $response->assertJsonPath('data.requires_otp', true);
        $response->assertJsonPath('data.email', 'user@example.com');
        $this->assertNull($response->json('data.token'));

        $this->assertDatabaseHas('email_otps', [
            'email' => 'user@example.com',
            'attempts' => 0,
        ]);

        Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    }

    public function test_customer_can_verify_login_otp_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'role' => UserRole::Customer,
            'email_verified_at' => now(),
        ]);

        EmailOtp::create([
            'email' => 'user@example.com',
            'otp' => '654321',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/verify-email-otp', [
            'email' => 'user@example.com',
            'otp' => '654321',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.token'));
        $response->assertJsonPath('data.user.email', 'user@example.com');
        $this->assertDatabaseMissing('email_otps', ['email' => 'user@example.com']);
    }

    public function test_technician_can_login_directly_with_token(): void
    {
        $tech = User::factory()->create([
            'email' => 'tech@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Technician,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'tech@example.com',
            'password' => 'password123',
            'role' => 'technician',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.user.email', 'tech@example.com');
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

    public function test_user_can_request_password_reset_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'resetuser@example.com',
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'resetuser@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('email_otps', [
            'email' => 'resetuser@example.com',
        ]);

        Notification::assertSentTo($user, ResetPasswordOtpNotification::class);
    }

    public function test_user_can_reset_password_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'resetuser@example.com',
            'password' => Hash::make('oldpassword123'),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        EmailOtp::create([
            'email' => 'resetuser@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetuser@example.com',
            'otp' => '123456',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Check password updated
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));

        // Check OTP deleted
        $this->assertDatabaseMissing('email_otps', ['email' => 'resetuser@example.com']);
    }

    public function test_reset_password_fails_with_invalid_otp(): void
    {
        User::factory()->create([
            'email' => 'resetuser@example.com',
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        EmailOtp::create([
            'email' => 'resetuser@example.com',
            'otp' => '123456',
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'resetuser@example.com',
            'otp' => '999999',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
