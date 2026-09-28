<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPlaceTest extends TestCase
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

    public function test_unauthenticated_users_cannot_access_place_autocomplete(): void
    {
        $response = $this->getJson(route('admin.places.autocomplete', ['query' => 'milanpally']));

        $response->assertUnauthorized();
    }

    public function test_autocomplete_returns_all_places_api_suggestions(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places:autocomplete' => Http::response([
                'suggestions' => [
                    [
                        'placePrediction' => [
                            'placeId' => 'place_id_1',
                            'text' => ['text' => 'Milanpally, Siliguri, West Bengal, India'],
                            'structuredFormat' => [
                                'mainText' => ['text' => 'Milanpally'],
                                'secondaryText' => ['text' => 'Siliguri, West Bengal, India'],
                            ],
                        ],
                    ],
                    [
                        'placePrediction' => [
                            'placeId' => 'place_id_2',
                            'text' => ['text' => 'Milanpally Childrens Park, New Milanpally, Siliguri, India'],
                            'structuredFormat' => [
                                'mainText' => ['text' => 'Milanpally Childrens Park'],
                                'secondaryText' => ['text' => 'New Milanpally, Siliguri, India'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.places.autocomplete', ['query' => 'milanpally']));

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment([
            'place_id' => 'place_id_1',
            'main_text' => 'Milanpally',
            'secondary_text' => 'Siliguri, West Bengal, India',
        ]);
        $response->assertJsonFragment([
            'place_id' => 'place_id_2',
            'main_text' => 'Milanpally Childrens Park',
            'secondary_text' => 'New Milanpally, Siliguri, India',
        ]);
    }

    public function test_details_returns_formatted_address_and_coordinates(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places/place_id_2' => Http::response([
                'id' => 'place_id_2',
                'formattedAddress' => 'New Milanpally, Saktigarh, Siliguri, West Bengal 734005, India',
                'location' => [
                    'latitude' => 26.6997379,
                    'longitude' => 88.4182167,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.places.details', ['place_id' => 'place_id_2']));

        $response->assertOk();
        $response->assertJson([
            'formatted_address' => 'New Milanpally, Saktigarh, Siliguri, West Bengal 734005, India',
            'lat' => 26.6997379,
            'lng' => 88.4182167,
        ]);
    }
}
