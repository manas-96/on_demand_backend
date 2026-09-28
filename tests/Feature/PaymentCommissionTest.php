<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_service_uses_provider_custom_commission_percentage(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $provider = User::factory()->create(['role' => UserRole::Provider]);

        // Create ProviderProfile with 15% custom commission
        ProviderProfile::create([
            'user_id' => $provider->id,
            'company_name' => 'Custom Commission Plumbing',
            'commission' => 15.00,
            'is_approved' => true,
        ]);

        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Pipe Repair', 'slug' => 'pipe-repair', 'base_price' => 1000.00]);

        $sr = ServiceRequest::create([
            'request_number' => 'SR-COMM-001',
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Pipe repair request',
            'address_line' => '100 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::InProgress,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-COMM-001',
            'service_request_id' => $sr->id,
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'subtotal' => 1000.00,
            'tax' => 0.00,
            'discount' => 0.00,
            'total' => 1000.00,
            'status' => InvoiceStatus::Issued,
        ]);

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment($invoice, 'TXN-COMM-100', 1000.00);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'transaction_id' => 'TXN-COMM-100',
            'amount' => 1000.00,
        ]);

        // 15% of 1000 = 150.00 platform fee, net amount = 850.00
        $this->assertDatabaseHas('provider_earnings', [
            'provider_id' => $provider->id,
            'invoice_id' => $invoice->id,
            'gross_amount' => 1000.00,
            'platform_fee' => 150.00,
            'net_amount' => 850.00,
        ]);
    }

    public function test_payment_commission_calculation_with_tax_and_tips(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $provider = User::factory()->create(['role' => UserRole::Provider]);

        ProviderProfile::create([
            'user_id' => $provider->id,
            'company_name' => 'Tax and Tip Test Provider',
            'commission' => 11.00, // 11% commission
            'is_approved' => true,
        ]);

        $category = ServiceCategory::create(['name' => 'Electrical', 'slug' => 'electrical-test', 'is_active' => true]);
        $service = Service::create(['service_category_id' => $category->id, 'name' => 'Wiring', 'slug' => 'wiring-test', 'base_price' => 200.00]);

        $sr = ServiceRequest::create([
            'request_number' => 'SR-TAXTIP-01',
            'customer_id' => $customer->id,
            'service_category_id' => $category->id,
            'service_id' => $service->id,
            'request_type' => 'broadcast',
            'description' => 'Wiring repair request',
            'address_line' => '200 Main St',
            'latitude' => 28.6139,
            'longitude' => 77.2090,
            'preferred_date' => now()->toDateString(),
            'status' => ServiceRequestStatus::InProgress,
        ]);

        // Labor: 1235.00, Material: 5563.00 -> Service Cost: 6798.00
        // Tax (5%): 339.90 -> Grand Total: 7137.90
        // Tip: 20.00 -> Gross Payment Billed: 7157.90
        // Commission: 11% -> Admin Profit: 6798.00 * 0.11 = 747.78
        // Provider Pay: 7137.90 - 747.78 + 20.00 = 6410.12
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TAXTIP-01',
            'service_request_id' => $sr->id,
            'customer_id' => $customer->id,
            'provider_id' => $provider->id,
            'subtotal' => 6798.00,
            'tax' => 339.90,
            'discount' => 0.00,
            'tip_amount' => 20.00,
            'total' => 7157.90,
            'status' => InvoiceStatus::Issued,
        ]);

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        $paymentService->recordPayment($invoice, 'TXN-TAXTIP-200', 7157.90);

        $this->assertDatabaseHas('provider_earnings', [
            'provider_id' => $provider->id,
            'invoice_id' => $invoice->id,
            'gross_amount' => 7157.90,
            'platform_fee' => 747.78,
            'net_amount' => 6410.12,
        ]);
    }
}
