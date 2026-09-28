<?php

namespace Tests\Feature;

use App\Enums\ProviderBidStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\ProviderBid;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Bidding\BiddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StateTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $provider1;

    protected User $provider2;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'service_category_id' => $category->id,
            'name' => 'Leak Fix',
            'slug' => 'leak-fix',
            'base_price' => 500.00,
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer,
        ]);

        $this->provider1 = User::factory()->create([
            'role' => UserRole::Provider,
        ]);

        $this->provider2 = User::factory()->create([
            'role' => UserRole::Provider,
        ]);
    }

    public function test_bid_selection_atomically_awards_winning_provider_and_rejects_competitors(): void
    {
        $request = ServiceRequest::create([
            'request_number' => 'SR-TEST-001',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->service->service_category_id,
            'service_id' => $this->service->id,
            'request_type' => 'broadcast',
            'description' => 'Fix leaking pipe',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $bid1 = ProviderBid::create([
            'service_request_id' => $request->id,
            'provider_id' => $this->provider1->id,
            'amount' => 450.00,
            'status' => ProviderBidStatus::Submitted,
        ]);

        $bid2 = ProviderBid::create([
            'service_request_id' => $request->id,
            'provider_id' => $this->provider2->id,
            'amount' => 500.00,
            'status' => ProviderBidStatus::Submitted,
        ]);

        $biddingService = app(BiddingService::class);
        $result = $biddingService->selectBid($request, $bid1);

        $this->assertTrue($result);
        $this->assertEquals(ServiceRequestStatus::Awarded, $request->fresh()->status);
        $this->assertEquals(ProviderBidStatus::Accepted, $bid1->fresh()->status);
        $this->assertEquals(ProviderBidStatus::Rejected, $bid2->fresh()->status);
    }

    public function test_cannot_select_bid_for_already_awarded_request(): void
    {
        $request = ServiceRequest::create([
            'request_number' => 'SR-TEST-002',
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->service->service_category_id,
            'service_id' => $this->service->id,
            'request_type' => 'broadcast',
            'description' => 'Fix leaking pipe',
            'address_line' => '123 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Awarded,
        ]);

        $bid1 = ProviderBid::create([
            'service_request_id' => $request->id,
            'provider_id' => $this->provider1->id,
            'amount' => 450.00,
            'status' => ProviderBidStatus::Submitted,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $biddingService = app(BiddingService::class);
        $biddingService->selectBid($request, $bid1);
    }
}
