<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_fetch_parent_categories_and_subcategories(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $parent = ServiceCategory::create([
            'name' => 'Interior Repairs',
            'slug' => 'interior-repairs',
            'is_active' => true,
        ]);

        $sub1 = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        $sub2 = ServiceCategory::create([
            'name' => 'Refrigeration Repairs',
            'slug' => 'refrigeration-repairs',
            'parent_id' => $parent->id,
            'is_active' => true,
        ]);

        // 1. Fetch parent categories
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customer/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $parent->id)
            ->assertJsonPath('data.0.subcategories_count', 2);

        // 2. Fetch subcategories for parent
        $subResponse = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/customer/categories/{$parent->id}/subcategories");

        $subResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.parent_id', $parent->id);
    }

    public function test_admin_can_view_parent_and_create_subcategory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $parent = ServiceCategory::create([
            'name' => 'Cleaning & Organization',
            'slug' => 'cleaning-organization',
            'is_active' => true,
        ]);

        // Admin views parent category subcategories page
        $response = $this->actingAs($admin)
            ->get(route('admin.services.show', $parent->id));

        $response->assertStatus(200)
            ->assertSee('Cleaning & Organization')
            ->assertSee('Add Subcategory');

        // Admin stores subcategory
        $storeResponse = $this->actingAs($admin)
            ->post(route('admin.services.store'), [
                'name' => 'Deep Cleaning',
                'parent_id' => $parent->id,
            ]);

        $storeResponse->assertRedirect(route('admin.services.show', $parent->id));

        $this->assertDatabaseHas('service_categories', [
            'name' => 'Deep Cleaning',
            'parent_id' => $parent->id,
        ]);
    }
}
