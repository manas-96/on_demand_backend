<?php

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
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
     * Record payment and update provider earnings idempotently.
     */
    public function recordPayment(Invoice $invoice, string $transactionId, float $amount, string $gateway = 'stripe', array $details = []): Payment
    {
        return DB::transaction(function () use ($invoice, $transactionId, $amount, $gateway, $details) {
            // Idempotency check: if transaction already exists, return existing payment
            $existing = Payment::where('transaction_id', $transactionId)->first();
            if ($existing) {
                return $existing;
            }

            if ($amount < (float) $invoice->total) {
                throw new InvalidArgumentException('Payment amount is less than invoice total.');
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'payment_gateway' => $gateway,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'status' => PaymentStatus::Successful,
                'payment_details' => $details,
                'paid_at' => now(),
            ]);

            $invoice->update(['status' => InvoiceStatus::Paid]);
            $invoice->serviceRequest->update(['status' => ServiceRequestStatus::Completed]);

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

            return $payment;
        });
    }
}
