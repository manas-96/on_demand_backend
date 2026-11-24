<?php

namespace Tests\Feature;

use App\Models\ProviderProfile;
use App\Models\RequestPhoto;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProviderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_fetch_active_providers(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $providerUser = User::factory()->create(['role' => 'provider', 'name' => 'John Provider']);

        ProviderProfile::create([
            'user_id' => $providerUser->id,
            'company_name' => 'Elite Services',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/customer/providers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Active providers retrieved successfully.',
            ])
            ->assertJsonFragment([
                'company_name' => 'Elite Services',
                'name' => 'John Provider',
            ]);
    }

    public function test_customer_can_fetch_nearest_providers_sorted_by_distance(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $farProviderUser = User::factory()->create(['role' => 'provider', 'name' => 'Far Provider']);
        ProviderProfile::create([
            'user_id' => $farProviderUser->id,
            'company_name' => 'Faraway Services',
            'latitude' => 28.7041, // ~10km away
            'longitude' => 77.1025,
            'service_radius_km' => 50,
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);

        $nearProviderUser = User::factory()->create(['role' => 'provider', 'name' => 'Near Provider']);
        ProviderProfile::create([
            'user_id' => $nearProviderUser->id,
            'company_name' => 'Nearby Services',
            'latitude' => 28.6145, // ~0.1km away
            'longitude' => 77.2095,
            'service_radius_km' => 25,
            'is_approved' => true,
            'is_accepting_jobs' => true,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/customer/providers?latitude=28.6139&longitude=77.2090');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('Nearby Services', $data[0]['company_name']);
        $this->assertEquals('Faraway Services', $data[1]['company_name']);
        $this->assertLessThan($data[1]['distance_km'], $data[0]['distance_km']);
    }

    public function test_customer_can_create_service_request_with_uploaded_photos(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create(['role' => 'customer']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        $photoFile = UploadedFile::fake()->image('problem.jpg');

        $payload = [
            'service_category_id' => $category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaking pipe under kitchen sink',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->addDay()->toDateString(),
            'photos' => [$photoFile],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/customer/requests', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Service request created. Pay the booking deposit to confirm it.',
            ]);

        $requestId = $response->json('data.id');
        $this->assertDatabaseHas('request_photos', [
            'service_request_id' => $requestId,
        ]);

        $photo = RequestPhoto::where('service_request_id', $requestId)->first();
        Storage::disk('public')->assertExists($photo->file_path);
    }

    public function test_storage_media_route_serves_uploaded_file_directly(): void
    {
        $dir = storage_path('app/public/requests');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filePath = 'requests/test_sample_image.jpg';
        file_put_contents(storage_path('app/public/'.$filePath), 'fake_image_content');

        $response = $this->get('/storage/'.$filePath);
        $response->assertStatus(200);

        if (file_exists(storage_path('app/public/'.$filePath))) {
            unlink(storage_path('app/public/'.$filePath));
        }
    }

    public function test_deleting_request_photo_deletes_image_file_from_disk(): void
    {
        Storage::fake('public');

        $customer = User::factory()->create(['role' => 'customer']);
        $category = ServiceCategory::create(['name' => 'Cleaning', 'slug' => 'cleaning', 'is_active' => true]);
        $sr = ServiceRequest::create([
            'request_number' => 'SR-DEL-001',
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'request_type' => 'broadcast',
            'description' => 'Test deletion',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => 'submitted',
        ]);

        $filePath = 'requests/test_to_delete.jpg';
        Storage::disk('public')->put($filePath, 'image-data');
        Storage::disk('public')->assertExists($filePath);

        $photo = RequestPhoto::create([
            'service_request_id' => $sr->id,
            'file_path' => $filePath,
        ]);

        $photo->delete();

        Storage::disk('public')->assertMissing($filePath);
    }
}
