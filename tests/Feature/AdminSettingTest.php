<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_settings(): void
    {
        $response = $this->get(route('admin.settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('System Settings');
        $response->assertSee('Platform Fee');
        $response->assertSee('Minimum Booking Charge');
    }

    public function test_admin_can_update_system_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.settings.update'), [
                'platform_fee' => 15.50,
                'tax_rate' => 8.25,
                'minimum_booking_charge' => 35.00,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $settings = SystemSetting::getSettings();
        $this->assertEquals(15.50, (float) $settings->platform_fee);
        $this->assertEquals(8.25, (float) $settings->tax_rate);
        $this->assertEquals(35.00, (float) $settings->minimum_booking_charge);
    }

    public function test_public_can_fetch_api_v1_settings(): void
    {
        $response = $this->getJson('/api/v1/settings');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'tax_rate' => 5.00,
                'tax_rate_decimal' => 0.05,
            ],
        ]);
    }
}
