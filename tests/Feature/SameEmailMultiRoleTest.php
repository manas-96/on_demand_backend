<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\EmailOtp;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Auth\EmailOtpService;
use App\Services\Matching\ProviderMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Approach B: one email may own one account per role (e.g. customer + provider), never two of the same role.
 */
class SameEmailMultiRoleTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'x@x.com';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();
    }

    public function test_same_email_can_register_as_customer_and_as_provider(): void
    {
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        $this->postJson('/api/v1/auth/register', $this->customerSignup())->assertCreated();

        $this->post('/register/provider', [
            'company_name' => 'X Plumbing',
            'address' => '1 Main St',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'service_radius_km' => 20,
            'name' => 'X',
            'email' => self::EMAIL,
            'phone' => '+15550001111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'categories' => [$category->id],
        ])->assertRedirect(route('provider.onboarding.verify-email'));

        $this->assertSame(2, User::where('email', self::EMAIL)->count());
        $this->assertEqualsCanonicalizing(['customer', 'provider'], User::where('email', self::EMAIL)->pluck('role')->map->value->all());
    }

    public function test_same_email_cannot_have_two_accounts_with_the_same_role(): void
    {
        $this->postJson('/api/v1/auth/register', $this->customerSignup())->assertCreated();

        $this->postJson('/api/v1/auth/register', $this->customerSignup(['phone' => '+15559999999']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->postJson('/api/v1/auth/register', $this->customerSignup(['email' => 'other@x.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_api_login_uses_the_role_the_app_asks_for(): void
    {
        $this->accounts();

        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'customer-pass', 'role' => 'customer'])
            ->assertOk()
            ->assertJsonPath('data.role', 'customer');

        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'provider-pass', 'role' => 'provider'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'provider');
    }

    public function test_api_login_without_role_falls_back_to_the_customer_account(): void
    {
        $this->accounts();

        // Older customer-app builds send no role.
        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'customer-pass'])
            ->assertOk()
            ->assertJsonPath('data.role', 'customer');
    }

    public function test_right_password_for_another_role_gets_a_clear_message(): void
    {
        User::factory()->create(['email' => self::EMAIL, 'role' => UserRole::Customer, 'password' => Hash::make('customer-pass')]);

        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'customer-pass', 'role' => 'technician'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Access denied. Account is not registered as a technician.');

        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'wrong', 'role' => 'technician'])
            ->assertStatus(401);
    }

    public function test_otp_codes_of_the_two_accounts_do_not_interfere(): void
    {
        [$customer, $provider] = $this->accounts();

        // Customer login issues a customer code; a provider code is issued separately.
        $this->postJson('/api/v1/auth/login', ['email' => self::EMAIL, 'password' => 'customer-pass', 'role' => 'customer'])->assertOk();
        app(EmailOtpService::class)->issue($provider);

        $customerCode = EmailOtp::forUser($customer)->value('otp');
        $providerCode = EmailOtp::forUser($provider)->value('otp');
        $this->assertNotNull($customerCode);
        $this->assertNotNull($providerCode);

        $this->postJson('/api/v1/auth/verify-email-otp', ['email' => self::EMAIL, 'role' => 'customer', 'otp' => $customerCode])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'customer');

        // The provider's code is untouched by the customer verification.
        $this->assertSame($providerCode, EmailOtp::forUser($provider)->value('otp'));
    }

    public function test_password_reset_only_changes_the_requested_account(): void
    {
        [$customer, $provider] = $this->accounts();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => self::EMAIL, 'role' => 'customer'])->assertOk();
        $code = EmailOtp::forUser($customer)->value('otp');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => self::EMAIL,
            'role' => 'customer',
            'otp' => $code,
            'password' => 'new-customer-pass',
            'password_confirmation' => 'new-customer-pass',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-customer-pass', $customer->fresh()->password));
        $this->assertTrue(Hash::check('provider-pass', $provider->fresh()->password));
    }

    public function test_web_login_signs_in_the_provider_account(): void
    {
        [, $provider] = $this->accounts();

        $this->post('/login', ['email' => self::EMAIL, 'password' => 'provider-pass'])->assertRedirect('/provider');
        $this->assertAuthenticatedAs($provider);
    }

    public function test_web_login_with_only_the_customer_password_points_to_the_app(): void
    {
        $this->accounts();

        $this->post('/login', ['email' => self::EMAIL, 'password' => 'customer-pass'])
            ->assertSessionHasErrors(['email' => 'This web panel is for providers and admins. Please sign in with the Grab A Hand mobile app.']);
        $this->assertGuest();
    }

    public function test_web_login_prefers_admin_when_an_email_is_admin_and_provider(): void
    {
        $admin = User::factory()->create(['email' => self::EMAIL, 'role' => UserRole::Admin, 'password' => Hash::make('same-pass')]);
        User::factory()->create(['email' => self::EMAIL, 'role' => UserRole::Provider, 'password' => Hash::make('same-pass')]);

        $this->post('/login', ['email' => self::EMAIL, 'password' => 'same-pass'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_customer_request_is_never_offered_to_their_own_company(): void
    {
        [$customer, $ownCompany] = $this->accounts();
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        $otherCompany = $this->approvedProvider(User::factory()->create(['role' => UserRole::Provider]), 'Other Co');
        $this->approvedProvider($ownCompany, 'X Plumbing');

        $request = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'request_type' => 'broadcast',
            'description' => 'Leak',
            'address_line' => '1 Main St',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'preferred_date' => now()->addDay()->toDateString(),
            'deposit_amount' => 0,
        ]);

        app(ProviderMatchingService::class)->dispatchInvitations($request);

        $invited = ProviderJobRequest::where('service_request_id', $request->id)->pluck('provider_id')->all();
        $this->assertContains($otherCompany->user_id, $invited);
        $this->assertNotContains($ownCompany->id, $invited);
    }

    public function test_customer_cannot_send_a_direct_request_to_their_own_company(): void
    {
        [$customer, $ownCompany] = $this->accounts();
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        $this->actingAs($customer, 'sanctum')->postJson('/api/v1/customer/requests', [
            'service_category_id' => $category->id,
            'request_type' => 'direct',
            'target_provider_id' => $ownCompany->id,
            'description' => 'Leak',
            'address_line' => '1 Main St',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'preferred_date' => now()->addDay()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors(['target_provider_id' => 'You cannot book your own company.']);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function accounts(): array
    {
        $customer = User::factory()->create(['email' => self::EMAIL, 'role' => UserRole::Customer, 'password' => Hash::make('customer-pass')]);
        $provider = User::factory()->create(['email' => self::EMAIL, 'role' => UserRole::Provider, 'password' => Hash::make('provider-pass')]);

        return [$customer, $provider];
    }

    private function approvedProvider(User $user, string $company): ProviderProfile
    {
        return ProviderProfile::create([
            'user_id' => $user->id,
            'company_name' => $company,
            'latitude' => 40.7,
            'longitude' => -74.0,
            'service_radius_km' => 50,
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function customerSignup(array $overrides = []): array
    {
        return array_merge([
            'name' => 'X',
            'email' => self::EMAIL,
            'phone' => '+15550001111',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'customer',
        ], $overrides);
    }
}
