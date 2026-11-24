<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mail\ProviderApprovedMail;
use App\Mail\ProviderRegisteredAdminMail;
use App\Models\EmailOtp;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProviderRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private ServiceCategory $parent;

    private ServiceCategory $child;

    private Service $parentService;

    private Service $childService;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Notification::fake();

        $this->parent = ServiceCategory::create(['name' => 'HVAC', 'slug' => 'hvac', 'is_active' => true]);
        $this->child = ServiceCategory::create(['name' => 'AC Repair', 'slug' => 'ac-repair', 'is_active' => true, 'parent_id' => $this->parent->id]);
        $this->parentService = Service::create(['service_category_id' => $this->parent->id, 'name' => 'HVAC Inspection', 'slug' => 'hvac-inspection', 'base_price' => 100]);
        $this->childService = Service::create(['service_category_id' => $this->child->id, 'name' => 'AC Cleaning', 'slug' => 'ac-cleaning', 'base_price' => 300]);
    }

    public function test_registration_page_groups_sub_categories_under_main_categories(): void
    {
        $this->get('/register/provider')
            ->assertOk()
            ->assertSee('Register as a Service Provider')
            ->assertSee('HVAC')
            ->assertSee('1 sub-categories')
            ->assertSee('data-child-of="'.$this->parent->id.'"', false);
    }

    public function test_location_and_categories_are_required(): void
    {
        $this->post('/register/provider', $this->payload(['latitude' => null, 'longitude' => null, 'categories' => []]))
            ->assertSessionHasErrors(['latitude', 'longitude', 'categories']);

        $this->assertDatabaseCount('provider_profiles', 0);
    }

    public function test_registration_creates_a_pending_unverified_provider_and_sends_a_code(): void
    {
        SystemSetting::getSettings()->update(['platform_fee' => 12.5]);

        $this->post('/register/provider', $this->payload())
            ->assertRedirect(route('provider.onboarding.verify-email'));

        $this->assertAuthenticated();

        $user = User::where('email', 'david@breezeair.com')->first();
        $this->assertSame(UserRole::Provider, $user->role);
        $this->assertFalse($user->hasVerifiedEmail());

        $profile = $user->providerProfile;
        $this->assertFalse($profile->is_approved);
        $this->assertEquals(40.7128, (float) $profile->latitude);
        $this->assertEquals(-74.006, (float) $profile->longitude);
        $this->assertEquals(12.5, (float) $profile->commission);

        // Picking the main category also offers the sub-category's services.
        $this->assertEqualsCanonicalizing(
            [$this->parentService->id, $this->childService->id],
            $profile->providerServices()->pluck('service_id')->all()
        );

        Notification::assertSentTo($user, VerifyEmailOtpNotification::class);
    }

    public function test_unverified_provider_is_sent_to_email_verification(): void
    {
        $this->post('/register/provider', $this->payload());

        $this->get('/provider/dashboard')->assertRedirect(route('provider.onboarding.verify-email'));
        $this->get(route('provider.onboarding.verify-email'))->assertOk()->assertSee('david@breezeair.com');
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->post('/register/provider', $this->payload());

        $this->post(route('provider.onboarding.verify-email.submit'), ['otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->assertFalse(User::where('email', 'david@breezeair.com')->first()->hasVerifiedEmail());
    }

    public function test_correct_code_verifies_email_and_notifies_admins(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email' => 'ops@example.com']);
        $this->post('/register/provider', $this->payload());

        $code = EmailOtp::where('email', 'david@breezeair.com')->value('otp');

        $this->post(route('provider.onboarding.verify-email.submit'), ['otp' => $code])
            ->assertRedirect(route('provider.onboarding.pending'));

        $this->assertTrue(User::where('email', 'david@breezeair.com')->first()->hasVerifiedEmail());
        Mail::assertSent(ProviderRegisteredAdminMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_verified_but_unapproved_provider_only_sees_the_pending_page(): void
    {
        $provider = $this->verifiedPendingProvider();

        $this->actingAs($provider)->get('/provider/dashboard')->assertRedirect(route('provider.onboarding.pending'));
        $this->actingAs($provider)->get('/provider/requests')->assertRedirect(route('provider.onboarding.pending'));
        $this->actingAs($provider)->get(route('provider.onboarding.pending'))->assertOk()->assertSee('under review');
    }

    public function test_admin_approval_emails_the_provider_and_unlocks_the_panel(): void
    {
        $provider = $this->verifiedPendingProvider();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('admin.providers.toggle-approval', $provider->providerProfile->id))->assertRedirect();

        Mail::assertSent(ProviderApprovedMail::class, fn ($mail) => $mail->hasTo($provider->email));
        $this->actingAs($provider->fresh())->get('/provider/dashboard')->assertOk();
        $this->actingAs($provider->fresh())->get(route('provider.onboarding.pending'))->assertRedirect(route('provider.dashboard'));
    }

    public function test_unapproved_provider_is_locked_out_again(): void
    {
        $provider = $this->verifiedPendingProvider(approved: true);
        $this->actingAs($provider)->get('/provider/dashboard')->assertOk();

        $provider->providerProfile->update(['is_approved' => false]);

        $this->actingAs($provider->fresh())->get('/provider/dashboard')->assertRedirect(route('provider.onboarding.pending'));
    }

    public function test_resend_respects_the_cooldown(): void
    {
        $this->post('/register/provider', $this->payload());

        $this->post(route('provider.onboarding.verify-email.resend'))->assertSessionHasErrors('otp');

        $this->travel(61)->seconds();

        $this->post(route('provider.onboarding.verify-email.resend'))->assertSessionHasNoErrors();
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register/provider', $this->payload(['email' => "spam{$i}@example.com"]));
            auth()->logout();
        }

        $this->post('/register/provider', $this->payload(['email' => 'spam6@example.com']))->assertStatus(429);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Breeze Air Services',
            'business_registration' => 'REG-554433',
            'address' => '100 Windmill Lane, Sector 8',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'service_radius_km' => 35.0,
            'name' => 'David Breeze',
            'email' => 'david@breezeair.com',
            'phone' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'categories' => [$this->parent->id, $this->child->id],
        ], $overrides);
    }

    private function verifiedPendingProvider(bool $approved = false): User
    {
        $provider = User::factory()->create(['role' => UserRole::Provider]);
        ProviderProfile::create([
            'user_id' => $provider->id,
            'company_name' => 'Pending Co',
            'latitude' => 40.7,
            'longitude' => -74.0,
            'is_approved' => $approved,
            'is_accepting_jobs' => true,
        ]);

        return $provider->fresh();
    }
}
