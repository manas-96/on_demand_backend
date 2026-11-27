<?php

namespace Tests\Feature;

use App\Enums\JobAssignmentStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Mail\BookingRescheduledMail;
use App\Models\JobAssignment;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RescheduleBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $provider;

    protected User $technician;

    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->provider = User::factory()->create(['role' => UserRole::Provider, 'email' => 'provider@example.com']);
        $this->technician = User::factory()->create(['role' => UserRole::Technician, 'email' => 'tech@example.com']);
        $this->category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    }

    public function test_customer_reschedules_and_provider_and_technician_are_emailed(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking();
        $this->assignTechnician($booking);

        $response = $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-02',
            'preferred_start_time' => '14:00',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.preferred_date', '2026-12-02')
            ->assertJsonPath('data.can_reschedule', true);
        $booking->refresh();
        $this->assertSame('2026-12-02', $booking->preferred_date->toDateString());
        $this->assertSame('14:00', Carbon::parse($booking->preferred_start_time)->format('H:i'));

        Mail::assertSent(BookingRescheduledMail::class, fn (BookingRescheduledMail $mail) => $mail->hasTo('provider@example.com')
            && $mail->recipientRole === 'provider'
            && $mail->previousSchedule === 'Mon, Nov 30, 2026 at 10:00 PM');
        Mail::assertSent(BookingRescheduledMail::class, fn (BookingRescheduledMail $mail) => $mail->hasTo('tech@example.com')
            && $mail->recipientRole === 'technician');
        Mail::assertSentCount(2);
    }

    /**
     * Booking: Nov 30, 10:00 PM in Asia/Kolkata = 16:30 UTC. Cutoff: Nov 29, 16:30 UTC.
     */
    #[DataProvider('cutoffMoments')]
    public function test_reschedule_cutoff_is_24_hours_before_the_slot_in_the_bookings_timezone(string $nowUtc, bool $allowed): void
    {
        $this->travelTo(Carbon::parse($nowUtc, 'UTC'));
        $booking = $this->createBooking(['timezone' => 'Asia/Kolkata']);

        $response = $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
            'preferred_start_time' => '10:00',
        ]);

        if ($allowed) {
            $response->assertOk();
            $this->assertSame('2026-12-05', $booking->fresh()->preferred_date->toDateString());
        } else {
            $response->assertUnprocessable()
                ->assertJsonPath('message', 'Bookings can only be rescheduled up to 24 hours before the scheduled time.');
            $this->assertSame('2026-11-30', $booking->fresh()->preferred_date->toDateString());
            Mail::assertNothingSent();
        }
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function cutoffMoments(): array
    {
        return [
            'one minute before cutoff' => ['2026-11-29 16:29', true],
            'at the cutoff' => ['2026-11-29 16:30', false],
            'after the cutoff' => ['2026-11-30 10:00', false],
        ];
    }

    public function test_booking_without_a_start_time_uses_the_start_of_the_day_for_the_cutoff(): void
    {
        // Nov 30 with no time → cutoff Nov 29 00:00 local (UTC here).
        $this->travelTo(Carbon::parse('2026-11-29 08:00', 'UTC'));
        $booking = $this->createBooking(['preferred_start_time' => null, 'timezone' => 'UTC']);

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
        ])->assertUnprocessable();
    }

    public function test_new_slot_must_be_in_the_future(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking(['timezone' => 'UTC']);

        $response = $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-11-27',
            'preferred_start_time' => '08:00',
        ]);

        $response->assertUnprocessable()->assertJsonPath('message', 'Please choose a date and time in the future.');
        $this->assertSame('2026-11-30', $booking->fresh()->preferred_date->toDateString());
    }

    public function test_same_slot_is_rejected_without_emailing(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking();

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-11-30',
            'preferred_start_time' => '22:00',
        ])->assertUnprocessable()->assertJsonPath('message', 'Your booking is already scheduled for that date and time.');

        Mail::assertNothingSent();
    }

    #[DataProvider('closedStatuses')]
    public function test_bookings_that_are_underway_or_closed_cannot_be_rescheduled(ServiceRequestStatus $status): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking(['status' => $status]);

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
            'preferred_start_time' => '10:00',
        ])->assertUnprocessable()->assertJsonPath('message', 'This booking can no longer be rescheduled.');

        $this->assertSame('2026-11-30', $booking->fresh()->preferred_date->toDateString());
    }

    /**
     * @return array<string, array{ServiceRequestStatus}>
     */
    public static function closedStatuses(): array
    {
        return [
            'in progress' => [ServiceRequestStatus::InProgress],
            'completed' => [ServiceRequestStatus::Completed],
            'cancelled' => [ServiceRequestStatus::Cancelled],
        ];
    }

    public function test_direct_booking_without_technician_emails_only_the_chosen_provider(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking(['request_type' => 'direct', 'target_provider_id' => $this->provider->id]);

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
            'preferred_start_time' => '10:00',
        ])->assertOk();

        Mail::assertSent(BookingRescheduledMail::class, fn (BookingRescheduledMail $mail) => $mail->hasTo('provider@example.com'));
        Mail::assertSentCount(1);
    }

    public function test_broadcast_booking_with_no_provider_yet_sends_no_email(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking(['status' => ServiceRequestStatus::Matching]);

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
            'preferred_start_time' => '10:00',
        ])->assertOk();

        Mail::assertNothingSent();
    }

    public function test_customer_cannot_reschedule_someone_elses_booking(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking();
        $otherCustomer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($otherCustomer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '2026-12-05',
            'preferred_start_time' => '10:00',
        ])->assertNotFound();

        $this->assertSame('2026-11-30', $booking->fresh()->preferred_date->toDateString());
    }

    public function test_reschedule_requires_a_valid_date(): void
    {
        $booking = $this->createBooking();

        $this->actingAs($this->customer, 'sanctum')->postJson("/api/v1/customer/requests/{$booking->id}/reschedule", [
            'preferred_date' => '30/12/2026',
        ])->assertUnprocessable()->assertJsonValidationErrors(['preferred_date' => 'The preferred date field must match the format Y-m-d.']);
    }

    public function test_booking_details_tell_the_app_whether_it_can_be_rescheduled(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 09:00', 'UTC'));
        $booking = $this->createBooking(['timezone' => 'Asia/Kolkata']);

        $this->actingAs($this->customer, 'sanctum')->getJson("/api/v1/customer/requests/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.can_reschedule', true)
            ->assertJsonPath('data.reschedule_deadline', '2026-11-29T16:30:00+00:00');

        $this->travelTo(Carbon::parse('2026-11-29 17:00', 'UTC'));

        $this->actingAs($this->customer, 'sanctum')->getJson("/api/v1/customer/requests/{$booking->id}")
            ->assertJsonPath('data.can_reschedule', false);
    }

    public function test_reschedule_email_shows_the_new_and_previous_schedule(): void
    {
        $booking = $this->createBooking(['preferred_date' => '2026-12-02', 'preferred_start_time' => '14:00']);

        $mail = new BookingRescheduledMail($booking, $this->technician, 'technician', 'Mon, Nov 30, 2026 at 10:00 PM');

        $mail->assertHasSubject("Grab A Hand - Booking Rescheduled: Request #{$booking->request_number}");
        $mail->assertSeeInHtml('Wed, Dec 02, 2026 at 2:00 PM');
        $mail->assertSeeInHtml('Mon, Nov 30, 2026 at 10:00 PM');
        $mail->assertSeeInHtml('a job assigned to you');
    }

    /**
     * A booking for Mon Nov 30, 2026 at 10:00 PM (customer's wall clock).
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createBooking(array $overrides = []): ServiceRequest
    {
        return ServiceRequest::create(array_merge([
            'request_number' => 'SR-RESCHED-'.fake()->unique()->numerify('####'),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaking kitchen tap',
            'address_line' => '12 Pipe Street',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => '2026-11-30',
            'preferred_start_time' => '22:00',
            'timezone' => 'UTC',
            'status' => ServiceRequestStatus::Assigned,
        ], $overrides));
    }

    private function assignTechnician(ServiceRequest $booking): void
    {
        JobAssignment::create([
            'service_request_id' => $booking->id,
            'provider_id' => $this->provider->id,
            'technician_id' => $this->technician->id,
            'assigned_at' => now(),
            'status' => JobAssignmentStatus::Assigned,
        ]);
    }
}
