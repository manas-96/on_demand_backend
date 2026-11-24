<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
    }

    public function test_guest_can_access_public_privacy_policy_page(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertSee('Privacy Policy');
        $response->assertSee('Information We Collect');
        $response->assertSee('privacy@grabahand.com');
    }

    public function test_guest_can_access_privacy_policy_api(): void
    {
        $response = $this->getJson('/api/v1/privacy-policy');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'privacy_policy',
                'updated_at',
            ],
        ]);
        $this->assertStringContainsString('Privacy Policy', $response->json('data.privacy_policy'));
    }

    public function test_non_admin_cannot_access_admin_privacy_policy_editor(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/privacy-policy');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_and_update_privacy_policy(): void
    {
        $editResponse = $this->actingAs($this->admin)->get('/admin/privacy-policy');
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Privacy Policy Editor');

        $updateResponse = $this->actingAs($this->admin)->post('/admin/privacy-policy', [
            'privacy_policy' => '# Updated Custom Privacy Policy Header\n\nThis is custom updated policy content.',
        ]);

        $updateResponse->assertRedirect('/admin/privacy-policy');
        $updateResponse->assertSessionHas('success');

        $settings = SystemSetting::getSettings();
        $this->assertStringContainsString('Updated Custom Privacy Policy Header', $settings->privacy_policy);

        // Check updated public page
        $publicResponse = $this->get('/privacy-policy');
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Updated Custom Privacy Policy Header');
    }
}
