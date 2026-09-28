<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Banner;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBannerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected ServiceCategory $parentCategory;

    protected ServiceCategory $subCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->parentCategory = ServiceCategory::create([
            'name' => 'AC Repair',
            'slug' => 'ac-repair',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->subCategory = ServiceCategory::create([
            'name' => 'AC Servicing',
            'slug' => 'ac-servicing',
            'parent_id' => $this->parentCategory->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_banner_management(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/banners');
        $response->assertStatus(403);
    }

    public function test_admin_can_list_banners(): void
    {
        Banner::create([
            'title' => 'Summer Special',
            'subtitle' => '20% off on all AC services',
            'highlight_text' => 'FLAT 20% OFF',
            'button_text' => 'Book Now',
            'service_category_id' => $this->parentCategory->id,
            'sub_category_id' => $this->subCategory->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/banners');
        $response->assertStatus(200);
        $response->assertSee('Summer Special');
        $response->assertSee('FLAT 20% OFF');
        $response->assertSee('Book Now');
    }

    public function test_admin_can_create_banner(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/banners', [
            'title' => 'Monsoon Cleaning Offer',
            'subtitle' => 'Deep clean your home before rain',
            'highlight_text' => 'HOT DEAL',
            'button_text' => 'Explore Services',
            'service_category_id' => $this->parentCategory->id,
            'sub_category_id' => $this->subCategory->id,
            'sort_order' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseHas('banners', [
            'title' => 'Monsoon Cleaning Offer',
            'subtitle' => 'Deep clean your home before rain',
            'highlight_text' => 'HOT DEAL',
            'button_text' => 'Explore Services',
            'service_category_id' => $this->parentCategory->id,
            'sub_category_id' => $this->subCategory->id,
        ]);
    }

    public function test_admin_can_update_banner(): void
    {
        $banner = Banner::create([
            'title' => 'Old Banner Title',
            'subtitle' => 'Old Subtitle',
            'highlight_text' => 'OLD',
            'button_text' => 'Click',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/banners/{$banner->id}", [
            'title' => 'Updated Banner Title',
            'subtitle' => 'Updated Subtitle',
            'highlight_text' => 'NEW DEAL',
            'button_text' => 'Order Now',
            'service_category_id' => $this->parentCategory->id,
            'sub_category_id' => $this->subCategory->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.banners.index'));
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'title' => 'Updated Banner Title',
            'highlight_text' => 'NEW DEAL',
            'button_text' => 'Order Now',
        ]);
    }

    public function test_admin_can_toggle_banner_status(): void
    {
        $banner = Banner::create([
            'title' => 'Toggle Status Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/banners/{$banner->id}/toggle-status");
        $response->assertRedirect();
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_banner(): void
    {
        $banner = Banner::create([
            'title' => 'Delete Me Banner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/banners/{$banner->id}");
        $response->assertRedirect(route('admin.banners.index'));
        $this->assertSoftDeleted('banners', ['id' => $banner->id]);
    }

    public function test_customer_api_can_fetch_active_banners(): void
    {
        Banner::create([
            'title' => 'Active Banner 1',
            'highlight_text' => 'SALE',
            'button_text' => 'Book',
            'service_category_id' => $this->parentCategory->id,
            'sub_category_id' => $this->subCategory->id,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Inactive Banner 2',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/customer/banners');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Active Banner 1');
        $response->assertJsonPath('data.0.highlight_text', 'SALE');
    }
}
