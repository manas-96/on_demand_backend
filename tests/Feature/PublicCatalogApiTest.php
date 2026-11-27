<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Banner;
use App\Models\PromotionalVideo;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceCategory $category;

    protected ServiceCategory $subcategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = ServiceCategory::create(['name' => 'Cleaning', 'slug' => 'cleaning', 'is_active' => true]);
        $this->subcategory = ServiceCategory::create([
            'name' => 'Deep Cleaning',
            'slug' => 'deep-cleaning',
            'parent_id' => $this->category->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_can_list_categories(): void
    {
        $response = $this->getJson('/api/v1/customer/categories');

        $response->assertOk()->assertJsonPath('data.0.name', 'Cleaning');
    }

    public function test_guest_can_view_a_category_and_its_subcategories(): void
    {
        $this->getJson("/api/v1/customer/categories/{$this->category->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Cleaning');

        $this->getJson("/api/v1/customer/categories/{$this->category->id}/subcategories")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Deep Cleaning');
    }

    public function test_guest_can_list_banners_and_videos(): void
    {
        Banner::create(['title' => 'Spring Clean Offer', 'image' => 'banners/spring.jpg', 'is_active' => true]);
        PromotionalVideo::create(['title' => 'How deep cleaning works', 'video' => 'fJ9rUzIMcZQ', 'is_active' => true]);

        $this->getJson('/api/v1/customer/banners')->assertOk()->assertJsonPath('data.0.title', 'Spring Clean Offer');
        $this->getJson('/api/v1/customer/videos')->assertOk()->assertJsonPath('data.0.title', 'How deep cleaning works');
    }

    public function test_guest_can_list_providers_without_contact_details(): void
    {
        $providerUser = User::factory()->create(['role' => UserRole::Provider, 'name' => 'Pat Provider']);
        ProviderProfile::create([
            'user_id' => $providerUser->id,
            'company_name' => 'Sparkle Co',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'is_approved' => true,
        ]);

        $response = $this->getJson('/api/v1/customer/providers');

        $response->assertOk()->assertJsonPath('data.0.company_name', 'Sparkle Co');
        $this->assertArrayNotHasKey('email', $response->json('data.0'));
        $this->assertArrayNotHasKey('phone', $response->json('data.0'));
    }

    public function test_signed_in_customer_still_gets_the_catalog(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/customer/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Cleaning');
    }

    #[DataProvider('accountEndpoints')]
    public function test_guest_cannot_reach_account_endpoints(string $method, string $uri): void
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function accountEndpoints(): array
    {
        return [
            'bookings' => ['GET', '/api/v1/customer/requests'],
            'create booking' => ['POST', '/api/v1/customer/requests'],
            'addresses' => ['GET', '/api/v1/customer/addresses'],
            'profile' => ['GET', '/api/v1/auth/me'],
            'notifications' => ['GET', '/api/v1/notifications'],
        ];
    }
}
