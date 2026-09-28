<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_list_add_update_delete_and_set_default_address(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        // 1. Add Address
        $storeResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/customer/addresses', [
                'label' => 'Home',
                'house_no' => 'Flat 402',
                'street_address' => 'Highrise Apartments, Main Street',
                'landmark' => 'Near City Mall',
                'city' => 'Mumbai',
                'pincode' => '400001',
                'address_line' => 'Flat 402, Highrise Apartments, Main Street',
                'latitude' => 28.6139,
                'longitude' => 77.2090,
                'is_default' => true,
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.label', 'Home')
            ->assertJsonPath('data.house_no', 'Flat 402')
            ->assertJsonPath('data.street_address', 'Highrise Apartments, Main Street')
            ->assertJsonPath('data.landmark', 'Near City Mall')
            ->assertJsonPath('data.city', 'Mumbai')
            ->assertJsonPath('data.pincode', '400001')
            ->assertJsonPath('data.is_default', true);

        $addressId = $storeResponse->json('data.id');

        // 2. Add second address
        $storeSecond = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/customer/addresses', [
                'label' => 'Work',
                'house_no' => 'Suite 500',
                'street_address' => 'Business Park',
                'city' => 'Delhi',
                'pincode' => '110001',
                'address_line' => 'Suite 500, Business Park',
                'latitude' => 28.6200,
                'longitude' => 77.2150,
                'is_default' => false,
            ]);

        $storeSecond->assertStatus(201);
        $secondAddressId = $storeSecond->json('data.id');

        // 3. List addresses
        $listResponse = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/customer/addresses');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // 4. Update address
        $updateResponse = $this->actingAs($customer, 'sanctum')
            ->putJson("/api/v1/customer/addresses/{$secondAddressId}", [
                'label' => 'Office HQ',
                'house_no' => 'Floor 5',
                'street_address' => 'Tech Tower',
                'city' => 'Noida',
                'pincode' => '201301',
                'address_line' => '456 Tech Tower, Floor 5',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.label', 'Office HQ')
            ->assertJsonPath('data.house_no', 'Floor 5')
            ->assertJsonPath('data.city', 'Noida')
            ->assertJsonPath('data.address_line', '456 Tech Tower, Floor 5');

        // 5. Set default address
        $defaultResponse = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/customer/addresses/{$secondAddressId}/default");

        $defaultResponse->assertStatus(200)
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $addressId,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $secondAddressId,
            'is_default' => true,
        ]);

        // 6. Delete address
        $deleteResponse = $this->actingAs($customer, 'sanctum')
            ->deleteJson("/api/v1/customer/addresses/{$addressId}");

        $deleteResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('customer_addresses', [
            'id' => $addressId,
        ]);
    }
}
