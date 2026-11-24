<?php

namespace Tests\Feature;

use App\Mail\AdminDiagnosticTestMail;
use App\Mail\ServiceRequestCreatedProviderMail;
use App\Mail\TechnicianAssignedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminEmailTestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_email_test_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.email-test.index'));

        $response->assertOk();
        $response->assertViewIs('admin.email_test.index');
        $response->assertSee('Email Diagnostics');
    }

    public function test_admin_can_send_general_diagnostic_test_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.email-test.send'), [
                'recipient_email' => 'test@example.com',
                'template_type' => 'general',
                'custom_subject' => 'Custom SMTP Test Subject',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(AdminDiagnosticTestMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_admin_can_send_service_request_created_test_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.email-test.send'), [
                'recipient_email' => 'provider@example.com',
                'template_type' => 'service_request_created',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(ServiceRequestCreatedProviderMail::class, function ($mail) {
            return $mail->hasTo('provider@example.com');
        });
    }

    public function test_admin_can_send_technician_assigned_test_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.email-test.send'), [
                'recipient_email' => 'technician@example.com',
                'template_type' => 'technician_assigned',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(TechnicianAssignedMail::class, function ($mail) {
            return $mail->hasTo('technician@example.com');
        });
    }

    public function test_non_admin_cannot_access_email_test_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)
            ->get(route('admin.email-test.index'));

        $response->assertForbidden();
    }
}
