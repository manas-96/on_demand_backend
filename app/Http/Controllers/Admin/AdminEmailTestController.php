<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminDiagnosticTestMail;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingCompletedMail;
use App\Mail\BookingCreatedCustomerMail;
use App\Mail\QuoteAcceptedByCustomerProviderMail;
use App\Mail\QuoteApprovedByProviderCustomerMail;
use App\Mail\RequestAcceptedOrAssignedCustomerMail;
use App\Mail\ServiceRequestCreatedProviderMail;
use App\Mail\TechnicianAssignedMail;
use App\Models\Quote;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminEmailTestController extends Controller
{
    /**
     * Display current SMTP settings health overview and test email form.
     */
    public function index(Request $request): View
    {
        $smtpConfig = [
            'mailer' => config('mail.default', 'smtp'),
            'host' => config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => config('mail.mailers.smtp.port', 465),
            'scheme' => config('mail.mailers.smtp.scheme', 'smtps'),
            'username' => config('mail.mailers.smtp.username'),
            'password_configured' => ! empty(config('mail.mailers.smtp.password')),
            'sendmail_path' => config('mail.mailers.sendmail.path', '/usr/sbin/sendmail -t -i'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'timeout' => config('mail.mailers.smtp.timeout', 10),
            'queue_connection' => config('queue.default', 'sync'),
        ];

        return view('admin.email_test.index', compact('smtpConfig'));
    }

    /**
     * Dispatch a test email and return diagnostic feedback.
     */
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'template_type' => ['required', 'string', 'in:general,booking_created_customer,service_request_created,request_accepted,technician_assigned,quote_approved,quote_accepted,booking_cancelled,booking_completed'],
            'custom_subject' => ['nullable', 'string', 'max:255'],
        ]);

        $recipientEmail = $validated['recipient_email'];
        $templateType = $validated['template_type'];
        $customSubject = $validated['custom_subject'] ?? null;

        $smtpDetails = [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'scheme' => config('mail.mailers.smtp.scheme'),
            'sendmail_path' => config('mail.mailers.sendmail.path'),
            'from_address' => config('mail.from.address'),
            'queue_connection' => config('queue.default'),
        ];

        try {
            $mailable = match ($templateType) {
                'booking_created_customer' => $this->buildBookingCreatedCustomerMail(),
                'service_request_created' => $this->buildServiceRequestCreatedMail(),
                'request_accepted' => $this->buildRequestAcceptedMail(),
                'technician_assigned' => $this->buildTechnicianAssignedMail(),
                'quote_approved' => $this->buildQuoteApprovedMail(),
                'quote_accepted' => $this->buildQuoteAcceptedMail(),
                'booking_cancelled' => $this->buildBookingCancelledMail(),
                'booking_completed' => $this->buildBookingCompletedMail(),
                default => new AdminDiagnosticTestMail(
                    recipientEmail: $recipientEmail,
                    customSubject: $customSubject ?: 'Grab A Hand - Admin SMTP Diagnostic Test',
                    smtpDetails: $smtpDetails
                ),
            };

            Mail::to($recipientEmail)->send($mailable);

            $transportName = config('mail.default');

            return back()->with('success', sprintf(
                'Test email (%s) accepted by %s transport for %s! If using local sendmail, verify whether your local MTA relays to external inboxes.',
                str_replace('_', ' ', strtoupper($templateType)),
                strtoupper($transportName),
                $recipientEmail
            ));
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', sprintf(
                    'Failed to send email via %s: %s',
                    config('mail.default'),
                    $e->getMessage()
                ));
        }
    }

    private function buildServiceRequestCreatedMail(): ServiceRequestCreatedProviderMail
    {
        $sr = ServiceRequest::with('category')->first() ?? $this->createMockServiceRequest();
        $provider = User::where('role', 'provider')->first() ?? User::factory()->create(['role' => 'provider']);

        return new ServiceRequestCreatedProviderMail($sr, $provider);
    }

    private function buildRequestAcceptedMail(): RequestAcceptedOrAssignedCustomerMail
    {
        $sr = ServiceRequest::with(['category', 'assignedTechnician'])->first() ?? $this->createMockServiceRequest();
        $provider = User::where('role', 'provider')->first() ?? User::factory()->create(['role' => 'provider']);

        return new RequestAcceptedOrAssignedCustomerMail($sr, $provider, null);
    }

    private function buildTechnicianAssignedMail(): TechnicianAssignedMail
    {
        $sr = ServiceRequest::with('category')->first() ?? $this->createMockServiceRequest();
        $technician = User::where('role', 'technician')->first() ?? User::factory()->create(['role' => 'technician']);
        $provider = User::where('role', 'provider')->first() ?? User::factory()->create(['role' => 'provider']);

        return new TechnicianAssignedMail($sr, $technician, $provider);
    }

    private function buildQuoteApprovedMail(): QuoteApprovedByProviderCustomerMail
    {
        $sr = ServiceRequest::first() ?? $this->createMockServiceRequest();
        $customer = $sr->customer ?? User::factory()->create(['role' => 'customer']);
        $quote = Quote::first() ?? $this->createMockQuote($sr);

        return new QuoteApprovedByProviderCustomerMail($sr, $customer, $quote);
    }

    private function buildQuoteAcceptedMail(): QuoteAcceptedByCustomerProviderMail
    {
        $sr = ServiceRequest::first() ?? $this->createMockServiceRequest();
        $provider = User::where('role', 'provider')->first() ?? User::factory()->create(['role' => 'provider']);
        $quote = Quote::first() ?? $this->createMockQuote($sr);

        return new QuoteAcceptedByCustomerProviderMail($sr, $provider, $quote);
    }

    private function buildBookingCreatedCustomerMail(): BookingCreatedCustomerMail
    {
        $sr = ServiceRequest::with('category')->first() ?? $this->createMockServiceRequest();
        $customer = $sr->customer ?? User::factory()->create(['role' => 'customer']);

        return new BookingCreatedCustomerMail($sr, $customer);
    }

    private function buildBookingCancelledMail(): BookingCancelledMail
    {
        $sr = ServiceRequest::with('category')->first() ?? $this->createMockServiceRequest();
        $customer = $sr->customer ?? User::factory()->create(['role' => 'customer']);

        return new BookingCancelledMail($sr, $customer, 'customer', 'customer', 'Client schedule conflict - requested cancellation.');
    }

    private function buildBookingCompletedMail(): BookingCompletedMail
    {
        $sr = ServiceRequest::first() ?? $this->createMockServiceRequest();
        $customer = $sr->customer ?? User::factory()->create(['role' => 'customer']);

        return new BookingCompletedMail($sr, $customer, 'customer');
    }

    private function createMockServiceRequest(): ServiceRequest
    {
        $category = ServiceCategory::first() ?? ServiceCategory::create([
            'name' => 'Plumbing Services',
            'slug' => 'plumbing-services-'.rand(100, 999),
            'is_active' => true,
        ]);
        $customer = User::factory()->create(['role' => 'customer']);

        return ServiceRequest::create([
            'service_category_id' => $category->id,
            'customer_id' => $customer->id,
            'request_number' => 'SR-DEMO-'.rand(100, 999),
            'description' => 'Diagnostic test email request for verifying SMTP mail pipeline.',
            'status' => 'completed',
            'address_line' => '123 Main Street, Suite 400',
            'latitude' => 22.5726,
            'longitude' => 88.3639,
            'preferred_date' => now()->toDateString(),
        ]);
    }

    private function createMockQuote(ServiceRequest $sr): Quote
    {
        $provider = User::where('role', 'provider')->first() ?? User::factory()->create(['role' => 'provider']);

        return Quote::create([
            'service_request_id' => $sr->id,
            'provider_id' => $provider->id,
            'labor_subtotal' => 100.00,
            'material_subtotal' => 50.00,
            'total' => 150.00,
            'status' => 'approved',
            'notes' => 'Includes parts, labor, and standard warranty.',
        ]);
    }
}
