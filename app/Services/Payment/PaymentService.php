<?php

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\ServiceRequestStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProviderEarning;
use App\Models\ProviderProfile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a successful balance payment against an invoice and finalize it.
     *
     * The booking deposit already collected for the request counts toward the invoice total.
     */
    public function recordPayment(Invoice $invoice, string $transactionId, float $amount, string $gateway = 'stripe', array $details = []): Payment
    {
        return DB::transaction(function () use ($invoice, $transactionId, $amount, $gateway, $details) {
            // Idempotency check: if transaction already exists, return existing payment
            $existing = Payment::where('transaction_id', $transactionId)->first();
            if ($existing) {
                return $existing;
            }

            $depositPaid = (float) ($invoice->serviceRequest?->depositPaidAmount() ?? 0.0);

            if (round($amount + $depositPaid, 2) < (float) $invoice->total) {
                throw new InvalidArgumentException('Payment amount is less than invoice total.');
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'service_request_id' => $invoice->service_request_id,
                'type' => PaymentType::Balance,
                'customer_id' => $invoice->customer_id,
                'payment_gateway' => $gateway,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'status' => PaymentStatus::Successful,
                'payment_details' => $details,
                'paid_at' => now(),
            ]);

            $this->finalizeInvoice($invoice);

            return $payment;
        });
    }

    /**
     * Mark an invoice as paid, complete its request and book provider earnings (idempotent).
     */
    public function finalizeInvoice(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::Paid]);
            $invoice->serviceRequest->update(['status' => ServiceRequestStatus::Completed]);

            if (ProviderEarning::where('invoice_id', $invoice->id)->exists()) {
                return $invoice;
            }

            // Ensure invoice has valid provider_id (auto-resolve if missing or erroneously set to customer)
            if (! $invoice->provider_id || (int) $invoice->provider_id === (int) $invoice->customer_id) {
                $resolvedProviderId = $invoice->serviceRequest?->resolveProviderId();
                if ($resolvedProviderId) {
                    $invoice->update(['provider_id' => $resolvedProviderId]);
                    $invoice->refresh();
                }
            }

            // Calculate provider earnings based on provider's custom commission rate (default 10%)
            $providerProfile = ProviderProfile::where('user_id', $invoice->provider_id)->first();
            $commissionRate = $providerProfile ? ((float) $providerProfile->commission / 100) : 0.10;

            $quote = $invoice->serviceRequest?->commercialQuote;
            $quoteLabor = (float) ($quote?->labor_subtotal ?? 0.00);
            $quoteMaterial = (float) ($quote?->material_subtotal ?? 0.00);
            $quotePreTaxSubtotal = $quoteLabor + $quoteMaterial;

            if ($quotePreTaxSubtotal > 0) {
                $serviceCost = $quotePreTaxSubtotal;
            } elseif ($quote && (float) $quote->subtotal > 0) {
                $serviceCost = (float) $quote->subtotal;
            } else {
                $serviceCost = (float) ($invoice->subtotal > 0 ? $invoice->subtotal : max(0.0, (float) $invoice->total - (float) ($invoice->tax ?? 0) - (float) ($invoice->tip_amount ?? 0)));
            }

            $tax = (float) ($quote?->tax ?? $invoice->tax ?? 0);
            $tips = (float) ($invoice->tip_amount ?? 0);
            $grandTotal = $quote ? (float) $quote->total : ($serviceCost + $tax);

            $platformFee = round($serviceCost * $commissionRate, 2);
            $netAmount = round($grandTotal - $platformFee + $tips, 2);

            ProviderEarning::create([
                'provider_id' => $invoice->provider_id,
                'service_request_id' => $invoice->service_request_id,
                'invoice_id' => $invoice->id,
                'gross_amount' => $grandTotal + $tips,
                'platform_fee' => $platformFee,
                'net_amount' => $netAmount,
            ]);

            return $invoice;
        });
    }
}
