<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\PromotionalVideo;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionalVideoTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_access_promotional_video_management(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/videos');
        $response->assertStatus(403);
    }

    public function test_admin_can_list_promotional_videos(): void
    {
        PromotionalVideo::create([
            'title' => 'AC Cleaning Video Demo',
            'description' => 'See how our AC deep cleaning works',
            'video' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ',
            'service_category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/videos');
        $response->assertStatus(200);
        $response->assertSee('AC Cleaning Video Demo');
        $response->assertSee('fJ9rUzIMcZQ');
    }

    public function test_admin_can_create_promotional_video(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/videos', [
            'title' => 'Water Leakage Repair Demo',
            'description' => 'Quick leak fix video demo',
            'video' => 'https://youtu.be/dQw4w9WgXcQ',
            'service_category_id' => $this->category->id,
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.videos.index'));
        $this->assertDatabaseHas('promotional_videos', [
            'title' => 'Water Leakage Repair Demo',
            'video' => 'https://youtu.be/dQw4w9WgXcQ',
            'service_category_id' => $this->category->id,
        ]);
    }

    public function test_admin_can_update_promotional_video(): void
    {
        $video = PromotionalVideo::create([
            'title' => 'Old Video Title',
            'video' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/videos/{$video->id}", [
            'title' => 'Updated Video Title',
            'video' => 'https://www.youtube.com/watch?v=L_LUpnjgPso',
            'service_category_id' => $this->category->id,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.videos.index'));
        $this->assertDatabaseHas('promotional_videos', [
            'id' => $video->id,
            'title' => 'Updated Video Title',
            'video' => 'https://www.youtube.com/watch?v=L_LUpnjgPso',
        ]);
    }

    public function test_admin_can_toggle_video_status(): void
    {
        $video = PromotionalVideo::create([
            'title' => 'Status Toggle Video',
            'video' => 'fJ9rUzIMcZQ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/videos/{$video->id}/toggle-status");
        $response->assertRedirect();
        $this->assertDatabaseHas('promotional_videos', [
            'id' => $video->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_promotional_video(): void
    {
        $video = PromotionalVideo::create([
            'title' => 'Delete Me Video',
            'video' => 'fJ9rUzIMcZQ',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/videos/{$video->id}");
        $response->assertRedirect(route('admin.videos.index'));
        $this->assertSoftDeleted('promotional_videos', ['id' => $video->id]);
    }

    public function test_customer_api_can_fetch_active_promotional_videos(): void
    {
        PromotionalVideo::create([
            'title' => 'Active Demo Video',
            'video' => 'https://www.youtube.com/watch?v=fJ9rUzIMcZQ',
            'service_category_id' => $this->category->id,
            'is_active' => true,
        ]);

        PromotionalVideo::create([
            'title' => 'Inactive Demo Video',
            'video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/customer/videos');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Active Demo Video');
        $response->assertJsonPath('data.0.youtube_video_id', 'fJ9rUzIMcZQ');
    }
}
