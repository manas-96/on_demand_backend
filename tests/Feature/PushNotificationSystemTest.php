<?php

namespace Tests\Feature;

use App\Enums\JobAssignmentStatus;
use App\Enums\NotificationType;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\DeviceToken;
use App\Models\JobAssignment;
use App\Models\Notification;
use App\Models\ProviderBid;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $provider;

    protected ProviderProfile $providerProfile;

    protected User $technician;

    protected User $admin;

    protected ServiceCategory $category;

    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forever('fcm_oauth2_access_token', 'mock_access_token');

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer->value,
        ]);

        $this->provider = User::factory()->create([
            'role' => UserRole::Provider->value,
        ]);
        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->provider->id,
            'company_name' => 'Test Provider Co',
            'is_approved' => true,
        ]);

        $this->technician = User::factory()->create([
            'role' => UserRole::Technician->value,
        ]);
        TechnicianProfile::create([
            'user_id' => $this->technician->id,
            'provider_id' => $this->provider->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin->value,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name' => 'Leak Repair',
            'slug' => 'leak-repair',
            'price' => 100.00,
        ]);
    }

    public function test_customer_can_register_and_update_device_token(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/device-token', [
                'device_token' => 'fcm_token_test_123',
                'platform' => 'android',
                'device_id' => 'device_android_001',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->customer->id,
            'device_token' => 'fcm_token_test_123',
            'platform' => 'android',
            'device_id' => 'device_android_001',
            'is_active' => true,
        ]);

        // Re-registering updates existing token
        $response2 = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/device-token', [
                'device_token' => 'fcm_token_test_123_updated',
                'platform' => 'android',
                'device_id' => 'device_android_001',
            ]);

        $response2->assertStatus(200);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->customer->id,
            'device_token' => 'fcm_token_test_123_updated',
            'device_id' => 'device_android_001',
            'is_active' => true,
        ]);
    }

    public function test_user_can_deactivate_device_token(): void
    {
        $token = DeviceToken::create([
            'user_id' => $this->customer->id,
            'device_token' => 'fcm_token_to_remove',
            'platform' => 'ios',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->deleteJson('/api/v1/device-token', [
                'device_token' => 'fcm_token_to_remove',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('device_tokens', [
            'id' => $token->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_fetch_and_read_notifications(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Test Notification',
            'body' => 'Test body message',
            'data' => ['screen' => 'booking_details'],
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/v1/notifications');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'unread_count' => 1])
            ->assertJsonCount(1, 'data');

        $notificationId = $response->json('data.0.id');

        $readResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/notifications/{$notificationId}/read");

        $readResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull(Notification::find($notificationId)->read_at);
    }

    public function test_provider_accepts_job_triggers_customer_notification(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'service_id' => $this->service->id,
            'description' => 'Test request',
            'address_line' => '123 Main St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Submitted,
        ]);

        $bid = ProviderBid::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'amount' => 150.00,
            'proposed_amount' => 150.00,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/bids/{$bid->id}/select");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Job Accepted',
        ]);
    }

    public function test_technician_assigned_triggers_customer_and_technician_notifications(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'service_id' => $this->service->id,
            'description' => 'Test assign request',
            'address_line' => '123 St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Awarded,
        ]);

        ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'provider_profile_id' => $this->providerProfile->id,
            'status' => 'selected',
        ]);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->postJson("/api/v1/provider/requests/{$serviceRequest->id}/assign-technician", [
                'technician_id' => $this->technician->id,
            ]);

        $response->assertStatus(200);

        // Customer notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Technician Assigned',
        ]);

        // Technician notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->technician->id,
            'type' => NotificationType::Booking->value,
            'title' => 'New Job Assignment',
        ]);
    }

    public function test_quotation_approved_triggers_customer_and_technician_notifications(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'service_id' => $this->service->id,
            'description' => 'Quote approve test',
            'address_line' => '123 St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::Assigned,
        ]);

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $quote = Quote::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'labor_subtotal' => 200.00,
            'material_subtotal' => 100.00,
            'tax' => 15.00,
            'discount' => 0.00,
            'total' => 315.00,
            'status' => QuoteStatus::Pending,
            'is_approved_by_provider' => true,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/quotes/{$quote->id}/approve");

        $response->assertStatus(200);

        // Customer notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Quotation Approved',
        ]);

        // Technician notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->technician->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Quotation Approved',
        ]);
    }

    public function test_job_completed_triggers_customer_and_technician_notifications(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $serviceRequest = ServiceRequest::create([
            'request_number' => ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'service_id' => $this->service->id,
            'description' => 'Job complete test',
            'address_line' => '123 St',
            'latitude' => 12.9716,
            'longitude' => 77.5946,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::InProgress,
        ]);

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::InProgress,
        ]);

        $response = $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/v1/technician/jobs/{$assignment->id}/complete");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Job Completed',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->technician->id,
            'type' => NotificationType::Booking->value,
            'title' => 'Job Completed',
        ]);
    }

    public function test_chat_notification_sent_to_receiver_and_skips_sender(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        // Provider sends chat notification to Customer
        $response = $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/chat/notify', [
                'job_id' => 'SR-20261003-000001',
                'receiver_id' => $this->customer->id,
                'message' => 'Hello customer, I will arrive at 10 AM.',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Message->value,
            'title' => 'New Message',
            'body' => 'Hello customer, I will arrive at 10 AM.',
        ]);

        // Sending to self skips notification
        $selfResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/chat/notify', [
                'job_id' => 'SR-20261003-000001',
                'receiver_id' => $this->customer->id,
                'message' => 'Self message',
            ]);

        $selfResponse->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_admin_can_send_promotional_notifications_via_api(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/notifications/promotional', [
                'title' => '20% Discount Offer',
                'body' => 'Use code ON_DEMAND_20 today!',
                'customer_ids' => [$this->customer->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'sent_count' => 1]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Promotional->value,
            'title' => '20% Discount Offer',
        ]);
    }

    public function test_admin_can_send_promotional_notification_via_web(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'mock_msg_id'], 200),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.notifications.send'), [
                'title' => 'Web Banner Offer',
                'body' => 'Web promotional push text',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => NotificationType::Promotional->value,
            'title' => 'Web Banner Offer',
        ]);
    }

    public function test_duplicate_events_do_not_generate_duplicate_notifications(): void
    {
        $service = app(PushNotificationService::class);

        $notif1 = $service->send(
            userId: $this->customer->id,
            title: 'Job Accepted',
            body: 'Accepted body',
            type: NotificationType::Booking->value,
            data: ['booking_id' => 'SR-100'],
            eventId: 'SR-100_accepted_1'
        );

        $notif2 = $service->send(
            userId: $this->customer->id,
            title: 'Job Accepted',
            body: 'Accepted body',
            type: NotificationType::Booking->value,
            data: ['booking_id' => 'SR-100'],
            eventId: 'SR-100_accepted_1'
        );

        $this->assertEquals($notif1->id, $notif2->id);
        $this->assertEquals(1, Notification::where('user_id', $this->customer->id)->count());
    }

    public function test_invalid_fcm_token_is_deactivated_automatically(): void
    {
        Http::fake([
            'https://fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'status' => 'UNREGISTERED',
                    'details' => [['errorCode' => 'UNREGISTERED']],
                ],
            ], 404),
        ]);

        $token = DeviceToken::create([
            'user_id' => $this->customer->id,
            'device_token' => 'dead_fcm_token',
            'platform' => 'android',
            'is_active' => true,
        ]);

        $service = app(PushNotificationService::class);
        $service->send(
            userId: $this->customer->id,
            title: 'Test Unregistered',
            body: 'Body',
            type: NotificationType::Booking->value
        );

        $this->assertFalse((bool) $token->fresh()->is_active);
    }
}
