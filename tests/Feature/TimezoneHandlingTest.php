<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\BusinessTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Storage is UTC; "today" and display depend on the customer / caller / business zone.
 *
 * Reference moment used below: 2026-11-04 23:00 UTC
 *   = Nov 4, 6:00 PM in New York  (the case reported by the client)
 *   = Nov 5, 4:30 AM in India     (what the old IST server showed)
 */
class TimezoneHandlingTest extends TestCase
{
    use RefreshDatabase;

    private const MOMENT_UTC = '2026-11-04 23:00:00';

    private User $customer;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['app.display_timezone' => 'America/New_York']);
        Carbon::setTestNow(Carbon::parse(self::MOMENT_UTC, 'UTC'));

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_app_runs_in_utc(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('2026-11-04T23:00:00+00:00', now()->toIso8601String());
    }

    public function test_local_today_differs_by_zone_for_the_reported_case(): void
    {
        $this->assertSame('2026-11-04', BusinessTime::today('America/New_York'));
        $this->assertSame('2026-11-05', BusinessTime::today('Asia/Kolkata'));
        $this->assertSame('2026-11-04', BusinessTime::today(), 'Falls back to the display timezone.');
    }

    public function test_display_macro_shows_business_time(): void
    {
        $this->assertSame('Nov 04, 6:00 PM', now()->inDisplayTimezone()->format('M d, g:i A'));
        $this->assertSame('Nov 05, 4:30 AM', now()->inDisplayTimezone('Asia/Kolkata')->format('M d, g:i A'));
    }

    public function test_local_day_converts_to_a_utc_range(): void
    {
        [$start, $end] = BusinessTime::utcRangeForLocalDates('2026-11-04', '2026-11-04', 'America/New_York');

        $this->assertSame('2026-11-04 05:00:00', $start->toDateTimeString());
        $this->assertSame('2026-11-05 04:59:59', $end->toDateTimeString());
        $this->assertSame('UTC', $start->timezoneName);
    }

    public function test_invalid_timezones_fall_back_to_the_display_zone(): void
    {
        $this->assertSame('America/New_York', BusinessTime::timezone('Mars/Olympus'));
        $this->assertSame('America/New_York', BusinessTime::timezone(''));
        $this->assertSame('Asia/Kolkata', BusinessTime::timezone('Asia/Kolkata'));
    }

    public function test_booking_stores_the_timezone_from_the_app_header(): void
    {
        $this->createRequest(headers: [BusinessTime::HEADER => 'America/Chicago'])
            ->assertCreated()
            ->assertJsonPath('data.timezone', 'America/Chicago');
    }

    public function test_explicit_timezone_field_wins_over_the_header(): void
    {
        $this->createRequest(['timezone' => 'America/Los_Angeles'], [BusinessTime::HEADER => 'America/Chicago'])
            ->assertCreated()
            ->assertJsonPath('data.timezone', 'America/Los_Angeles');
    }

    public function test_invalid_timezone_field_is_rejected(): void
    {
        $this->createRequest(['timezone' => 'Not/AZone'])->assertStatus(422)->assertJsonValidationErrors('timezone');
    }

    public function test_booking_without_any_zone_uses_the_display_timezone(): void
    {
        $this->createRequest()->assertCreated()->assertJsonPath('data.timezone', 'America/New_York');
    }

    public function test_api_timestamps_are_utc(): void
    {
        $this->createRequest()
            ->assertCreated()
            ->assertJsonPath('data.created_at', fn (string $value) => str_contains($value, '2026-11-04T23:00:00'));
    }

    public function test_technician_assignment_unlocks_on_the_customers_local_date(): void
    {
        // Booked for Nov 5. It is already Nov 5 in UTC and India, but still Nov 4 in New York.
        $newYork = $this->makeRequest('2026-11-05', 'America/New_York');
        $india = $this->makeRequest('2026-11-05', 'Asia/Kolkata');

        $this->assertFalse($newYork->isTechnicianAssignmentAllowed());
        $this->assertSame('Unlocks tomorrow (Nov 05, 2026)', $newYork->getAssignmentUnlockLabel());
        $this->assertTrue($india->isTechnicianAssignmentAllowed());
        $this->assertNull($india->getAssignmentUnlockLabel());
    }

    public function test_admin_today_filter_uses_the_business_day(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // 5:30 PM Nov 4 New York (22:30 UTC) -> part of New York's Nov 4.
        $evening = $this->makeRequest('2026-11-04', 'America/New_York', 'SR-NY-EVENING');
        DB::table('service_requests')->where('id', $evening->id)->update(['created_at' => '2026-11-04 22:30:00']);

        // 11 PM Nov 3 New York (04:00 UTC Nov 4) -> previous business day, even though it is Nov 4 in UTC.
        $previousDay = $this->makeRequest('2026-11-03', 'America/New_York', 'SR-NY-PREVIOUS');
        DB::table('service_requests')->where('id', $previousDay->id)->update(['created_at' => '2026-11-04 04:00:00']);

        $this->actingAs($admin)->get('/admin/requests?date_preset=today')
            ->assertOk()
            ->assertSee('SR-NY-EVENING')
            ->assertDontSee('SR-NY-PREVIOUS');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<string, string>  $headers
     */
    private function createRequest(array $overrides = [], array $headers = []): TestResponse
    {
        return $this->actingAs($this->customer, 'sanctum')->postJson('/api/v1/customer/requests', array_merge([
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Leaking tap',
            'address_line' => '1 Main St',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferred_date' => '2026-11-06',
        ], $overrides), $headers);
    }

    private function makeRequest(string $preferredDate, string $timezone, ?string $number = null): ServiceRequest
    {
        return ServiceRequest::create([
            'request_number' => $number ?? ServiceRequest::generateRequestNumber(),
            'customer_id' => $this->customer->id,
            'service_category_id' => $this->category->id,
            'request_type' => 'broadcast',
            'description' => 'Fix pipe',
            'address_line' => '1 Main St',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'preferred_date' => $preferredDate,
            'timezone' => $timezone,
            'status' => ServiceRequestStatus::Submitted,
        ]);
    }
}
