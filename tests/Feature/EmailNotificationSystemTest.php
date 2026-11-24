<?php

namespace Tests\Feature;

use App\Enums\JobAssignmentStatus;
use App\Enums\ProviderJobRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingCompletedMail;
use App\Mail\BookingCreatedCustomerMail;
use App\Mail\QuoteAcceptedByCustomerProviderMail;
use App\Mail\QuoteApprovedByProviderCustomerMail;
use App\Mail\RequestAcceptedOrAssignedCustomerMail;
use App\Mail\ServiceRequestCreatedProviderMail;
use App\Mail\TechnicianAssignedMail;
use App\Models\JobAssignment;
use App\Models\ProviderJobRequest;
use App\Models\ProviderProfile;
use App\Models\Quote;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\TechnicianProfile;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\Payment\StripeGateway;
use App\Services\Quote\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\Fakes\FakeStripeGateway;
use Tests\TestCase;

class EmailNotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $provider;

    protected ProviderProfile $providerProfile;

    protected User $technician;

    protected ServiceCategory $category;

    protected Service $service;

    protected FakeStripeGateway $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create([
            'role' => UserRole::Customer->value,
            'email' => 'customer@example.com',
        ]);

        $this->provider = User::factory()->create([
            'role' => UserRole::Provider->value,
            'email' => 'provider@example.com',
        ]);

        $this->providerProfile = ProviderProfile::create([
            'user_id' => $this->provider->id,
            'company_name' => 'Acme Plumbing Co',
            'is_approved' => true,
            'is_accepting_jobs' => true,
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'service_radius_km' => 50,
        ]);

        $this->technician = User::factory()->create([
            'role' => UserRole::Technician->value,
            'email' => 'tech@example.com',
        ]);

        TechnicianProfile::create([
            'user_id' => $this->technician->id,
            'provider_id' => $this->provider->id,
            'provider_profile_id' => $this->providerProfile->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $this->category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'minimum_booking_charge' => 50.00,
        ]);

        $this->service = Service::create([
            'service_category_id' => $this->category->id,
            'name' => 'Leak Repair',
            'slug' => 'leak-repair',
            'base_price' => 150.00,
        ]);

        $this->providerProfile->services()->attach($this->service->id);

        $this->stripe = new FakeStripeGateway;
        $this->app->instance(StripeGateway::class, $this->stripe);
    }

    public function test_trigger_1_service_request_created_queues_email_to_customer_and_providers(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/customer/requests', [
                'service_category_id' => $this->category->id,
                'request_type' => 'broadcast',
                'description' => 'Fix leaking kitchen pipe',
                'address_line' => '123 Market St',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'preferred_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(201);

        // Nothing goes out until the booking deposit is paid.
        Mail::assertNotSent(BookingCreatedCustomerMail::class);
        Mail::assertNotSent(ServiceRequestCreatedProviderMail::class);

        $requestId = $response->json('data.id');
        $intentId = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-intent", ['type' => 'deposit'])
            ->assertOk()
            ->json('data.payment_intent_id');

        $this->stripe->succeed($intentId);

        $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$requestId}/payment-confirm", ['payment_intent_id' => $intentId])
            ->assertOk();

        Mail::assertSent(BookingCreatedCustomerMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(ServiceRequestCreatedProviderMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });
    }

    public function test_trigger_2a_provider_accepting_request_queues_email_to_customer(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-ACC-01',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $jobRequest = ProviderJobRequest::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'provider_profile_id' => $this->providerProfile->id,
            'status' => ProviderJobRequestStatus::Sent,
            'sent_at' => now(),
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->provider)
            ->post("/provider/requests/{$jobRequest->id}/accept");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        Mail::assertSent(RequestAcceptedOrAssignedCustomerMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    public function test_trigger_2b_assigning_technician_queues_email_to_customer(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-0001',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->postJson("/api/v1/provider/requests/{$serviceRequest->id}/assign-technician", [
                'technician_id' => $this->technician->id,
            ]);

        $response->assertStatus(200);

        Mail::assertSent(RequestAcceptedOrAssignedCustomerMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(TechnicianAssignedMail::class, function ($mail) {
            return $mail->hasTo($this->technician->email);
        });
    }

    public function test_trigger_3_provider_approving_quote_queues_email_to_customer(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-0002',
            'status' => ServiceRequestStatus::InProgress,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $quote = Quote::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'labor_subtotal' => 100,
            'material_subtotal' => 50,
            'tax' => 7.5,
            'total' => 157.5,
            'status' => QuoteStatus::Pending,
            'is_approved_by_provider' => false,
        ]);

        $quoteService = app(QuoteService::class);
        $quoteService->approveByProvider($this->provider, $quote);

        Mail::assertSent(QuoteApprovedByProviderCustomerMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }

    public function test_trigger_4_customer_accepting_quote_queues_email_to_provider(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-0003',
            'status' => ServiceRequestStatus::InProgress,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $quote = Quote::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'labor_subtotal' => 100,
            'material_subtotal' => 50,
            'tax' => 7.5,
            'total' => 157.5,
            'status' => QuoteStatus::Pending,
            'is_approved_by_provider' => true,
        ]);

        $quoteService = app(QuoteService::class);
        $quoteService->approveQuote($this->customer, $quote);

        Mail::assertSent(QuoteAcceptedByCustomerProviderMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });
    }

    public function test_trigger_5_booking_completed_queues_email_to_customer_provider_and_technician(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-0004',
            'status' => ServiceRequestStatus::InProgress,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::InProgress,
        ]);

        $response = $this->actingAs($this->technician, 'sanctum')
            ->postJson("/api/v1/technician/jobs/{$assignment->id}/complete", [
                'description' => 'Job finished successfully',
            ]);

        $response->assertStatus(200);

        Mail::assertSent(BookingCompletedMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(BookingCompletedMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });

        Mail::assertSent(BookingCompletedMail::class, function ($mail) {
            return $mail->hasTo('tech@example.com');
        });
    }

    public function test_trigger_5a_customer_cancelling_booking_queues_email_to_customer_provider_and_technician(): void
    {
        Mail::fake();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'target_provider_id' => $this->provider->id,
            'request_number' => 'SR-TEST-CAN-01',
            'status' => ServiceRequestStatus::Assigned,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $assignment = JobAssignment::create([
            'service_request_id' => $serviceRequest->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/cancel", [
                'reason' => 'Schedule conflict, need to reschedule.',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(ServiceRequestStatus::Cancelled, $serviceRequest->fresh()->status);
        $this->assertEquals(JobAssignmentStatus::Declined, $assignment->fresh()->status);

        $this->assertDatabaseHas('job_status_histories', [
            'service_request_id' => $serviceRequest->id,
            'changed_by_type' => 'customer',
            'changed_by_id' => $this->customer->id,
            'new_status' => ServiceRequestStatus::Cancelled->value,
        ]);

        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });

        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo('tech@example.com');
        });
    }

    public function test_trigger_5b_admin_cancelling_booking_queues_email_to_customer_and_provider(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'email' => 'admin@example.com',
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'target_provider_id' => $this->provider->id,
            'request_number' => 'SR-TEST-CAN-02',
            'status' => ServiceRequestStatus::Awarded,
            'request_type' => 'broadcast',
            'description' => 'Pipe leak',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->post("/admin/requests/{$serviceRequest->id}/override-status", [
                'new_status' => 'cancelled',
                'reason' => 'Administrative cancellation due to policy violation.',
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(ServiceRequestStatus::Cancelled, $serviceRequest->fresh()->status);

        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });
    }

    public function test_customer_cannot_cancel_another_customers_booking(): void
    {
        $otherCustomer = User::factory()->create([
            'role' => UserRole::Customer->value,
            'email' => 'other@example.com',
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-AUTH-01',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Fix pipe',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->addDay()->toDateString(),
        ]);

        $response = $this->actingAs($otherCustomer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$serviceRequest->id}/cancel");

        $response->assertStatus(403);
        $this->assertNotEquals(ServiceRequestStatus::Cancelled, $serviceRequest->fresh()->status);
    }

    public function test_customer_cannot_cancel_already_cancelled_or_in_progress_or_completed_booking(): void
    {
        $completedRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-ST-COMP',
            'status' => ServiceRequestStatus::Completed,
            'request_type' => 'broadcast',
            'description' => 'Completed job',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $responseComp = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$completedRequest->id}/cancel");

        $responseComp->assertStatus(422);

        $inProgressRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-ST-INP',
            'status' => ServiceRequestStatus::InProgress,
            'request_type' => 'broadcast',
            'description' => 'In progress job',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $responseInp = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$inProgressRequest->id}/cancel");

        $responseInp->assertStatus(422);

        $cancelledRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-ST-CANC',
            'status' => ServiceRequestStatus::Cancelled,
            'request_type' => 'broadcast',
            'description' => 'Already cancelled job',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $responseCanc = $this->actingAs($this->customer, 'sanctum')
            ->postJson("/api/v1/customer/requests/{$cancelledRequest->id}/cancel");

        $responseCanc->assertStatus(422);
    }

    public function test_atomic_deduplication_prevents_duplicate_email_dispatch(): void
    {
        Mail::fake();
        Cache::flush();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-DEDUP-01',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Test dedup',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $emailService = app(EmailNotificationService::class);

        // First call should send the email
        $emailService->sendBookingCreatedToCustomer($serviceRequest);
        // Second call with same event, request, and recipient should be deduplicated
        $emailService->sendBookingCreatedToCustomer($serviceRequest);

        Mail::assertSent(BookingCreatedCustomerMail::class, 1);
    }

    public function test_deduplication_does_not_suppress_separate_legitimate_lifecycle_events(): void
    {
        Mail::fake();
        Cache::flush();

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'target_provider_id' => $this->provider->id,
            'request_number' => 'SR-TEST-DEDUP-02',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Test lifecycle dedup',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $emailService = app(EmailNotificationService::class);

        // 1. Booking created
        $emailService->sendBookingCreatedToCustomer($serviceRequest);

        // 2. Booking accepted
        $emailService->sendRequestAcceptedOrAssignedToCustomer($serviceRequest, $this->provider);

        // 3. Booking cancelled
        $emailService->sendBookingCancelled($serviceRequest, 'customer');

        // All 3 different lifecycle events should be dispatched to customer
        Mail::assertSent(BookingCreatedCustomerMail::class, 1);
        Mail::assertSent(RequestAcceptedOrAssignedCustomerMail::class, 1);
        Mail::assertSent(BookingCancelledMail::class, function ($mail) {
            return $mail->hasTo($this->customer->email);
        });
    }

    public function test_deduplication_lock_is_released_if_dispatch_fails(): void
    {
        Cache::flush();

        $emailService = app(EmailNotificationService::class);

        $eventType = 'booking_created_customer';
        $requestId = 99999;
        $email = 'test@example.com';

        // Acquire lock
        $firstAcquired = $emailService->shouldSendEmail($eventType, $requestId, $email);
        $this->assertTrue($firstAcquired);

        // Immediate retry should be blocked
        $secondAcquired = $emailService->shouldSendEmail($eventType, $requestId, $email);
        $this->assertFalse($secondAcquired);

        // Simulate failure release
        $emailService->releaseDeduplicationLock($eventType, $requestId, $email);

        // Subsequent retry after failure release should succeed
        $thirdAcquired = $emailService->shouldSendEmail($eventType, $requestId, $email);
        $this->assertTrue($thirdAcquired);
    }

    public function test_missing_or_empty_recipient_email_is_handled_gracefully(): void
    {
        Mail::fake();

        $customerWithoutEmail = User::factory()->create([
            'role' => UserRole::Customer->value,
            'email' => '',
        ]);

        $serviceRequest = ServiceRequest::create([
            'customer_id' => $customerWithoutEmail->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-NO-EMAIL',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'No email customer',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $emailService = app(EmailNotificationService::class);
        $emailService->sendBookingCreatedToCustomer($serviceRequest);

        Mail::assertNothingSent();
    }

    public function test_provider_service_request_mail_has_grab_a_hand_sender_and_title(): void
    {
        $serviceRequest = ServiceRequest::create([
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_number' => 'SR-TEST-GAH-01',
            'status' => ServiceRequestStatus::Submitted,
            'request_type' => 'broadcast',
            'description' => 'Electrical fix',
            'address_line' => '123 Market St',
            'latitude' => 37.7749,
            'longitude' => -122.4194,
            'preferred_date' => now()->toDateString(),
        ]);

        $mailable = new ServiceRequestCreatedProviderMail($serviceRequest, $this->provider);
        $envelope = $mailable->envelope();

        $this->assertSame('Grab A Hand', $envelope->from->name);
        $this->assertStringStartsWith('Grab A Hand - New Service Request Available', $envelope->subject);

        $rendered = $mailable->render();
        $this->assertStringContainsString('<title>Grab A Hand - New Service Request Available', $rendered);
        $this->assertStringContainsString('Grab A Hand — On-Demand Services', $rendered);
    }
}
