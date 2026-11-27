<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
    }

    public function test_guest_can_access_public_support_page(): void
    {
        $response = $this->get('/support');

        $response->assertStatus(200);
        $response->assertSee('Customer Support');
        $response->assertSee('Submit Support Inquiry');
        $response->assertSee('Frequently Asked Questions');
    }

    public function test_guest_can_submit_valid_support_ticket(): void
    {
        $response = $this->post('/support', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+15559990000',
            'category' => 'booking',
            'subject' => 'Question about upcoming booking',
            'message' => 'Hello, I have a question regarding technician arrival schedule.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('support_tickets', [
            'email' => 'jane@example.com',
            'category' => 'booking',
            'subject' => 'Question about upcoming booking',
            'status' => 'open',
        ]);
    }

    public function test_sql_injection_payload_is_safely_persisted_as_plain_string_without_executing(): void
    {
        $sqlInjectionPayload = "' UNION SELECT 1, 'admin', 'hacked@example.com', 'password', '1', '1', '1', '1', '1', '1' -- ";

        $response = $this->post('/support', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'phone' => '+15550000000',
            'category' => 'general',
            'subject' => $sqlInjectionPayload,
            'message' => 'Attempting SQL injection payload insertion test message.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $ticket = SupportTicket::where('email', 'attacker@example.com')->first();
        $this->assertNotNull($ticket);
        $this->assertStringContainsString('UNION SELECT', $ticket->subject);
    }

    public function test_api_guest_can_submit_support_ticket(): void
    {
        $response = $this->postJson('/api/v1/support', [
            'name' => 'API Customer',
            'email' => 'apicustomer@example.com',
            'category' => 'technical',
            'subject' => 'App Crash on Login',
            'message' => 'The mobile application crashes when attempting to view active bookings.',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'status' => 'open',
            ],
        ]);
    }

    public function test_non_admin_blocked_from_admin_support_panel(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/support');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_and_update_support_ticket_status(): void
    {
        $ticket = $this->createWebTicket();

        $indexResponse = $this->actingAs($this->admin)->get('/admin/support');
        $indexResponse->assertOk();
        $indexResponse->assertSee($ticket->ticket_number);

        $showResponse = $this->actingAs($this->admin)->get("/admin/support/web/{$ticket->id}");
        $showResponse->assertOk();
        $showResponse->assertSee('Invoice discrepancy');

        $updateResponse = $this->actingAs($this->admin)->post("/admin/support/web/{$ticket->id}/status", [
            'status' => 'resolved',
            'admin_notes' => 'Verified transaction log and refunded duplicate charge.',
        ]);

        $updateResponse->assertRedirect("/admin/support/web/{$ticket->id}");
        $updateResponse->assertSessionHas('success', 'Ticket SUP-TEST-001 updated.');
        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertSame('Verified transaction log and refunded duplicate charge.', $ticket->admin_notes);
    }

    public function test_admin_cannot_set_an_unknown_ticket_status(): void
    {
        $ticket = $this->createWebTicket();

        $response = $this->actingAs($this->admin)
            ->from("/admin/support/web/{$ticket->id}")
            ->post("/admin/support/web/{$ticket->id}/status", ['status' => 'deleted']);

        $response->assertRedirect("/admin/support/web/{$ticket->id}");
        $response->assertSessionHasErrors(['status' => 'The selected status is invalid.']);
        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_non_admin_cannot_update_a_website_ticket(): void
    {
        $ticket = $this->createWebTicket();

        $response = $this->actingAs($this->customer)->post("/admin/support/web/{$ticket->id}/status", [
            'status' => 'closed',
        ]);

        $response->assertForbidden();
        $this->assertSame('open', $ticket->fresh()->status);
    }

    private function createWebTicket(): SupportTicket
    {
        return SupportTicket::create([
            'ticket_number' => 'SUP-TEST-001',
            'name' => 'Sam Worker',
            'email' => 'sam@example.com',
            'category' => 'payment',
            'subject' => 'Invoice discrepancy',
            'message' => 'I was charged twice for booking.',
            'status' => 'open',
        ]);
    }
}
