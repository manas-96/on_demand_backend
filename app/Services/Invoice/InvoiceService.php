<?php

namespace App\Services\Invoice;

use App\Enums\InvoiceStatus;
use App\Models\AdditionalService;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate authoritative server invoice for a completed/in-progress service request.
     */
    public function generateInvoice(ServiceRequest $request, ?Quote $quote = null): Invoice
    {
        return DB::transaction(function () use ($request, $quote) {
            $invoiceNumber = Invoice::generateInvoiceNumber();
            $subtotal = 0.0;
            $itemsToCreate = [];

            if ($quote) {
                foreach ($quote->items as $qItem) {
                    $itemsToCreate[] = [
                        'description' => $qItem->description,
                        'amount' => $qItem->total_price,
                    ];
                    $subtotal += (float) $qItem->total_price;
                }
            }

            // Include approved additional services
            $approvedExtras = AdditionalService::where('service_request_id', $request->id)
                ->where('status', 'approved')
                ->get();

            foreach ($approvedExtras as $extra) {
                $itemsToCreate[] = [
                    'description' => 'Additional Service: '.$extra->description,
                    'amount' => $extra->amount,
                ];
                $subtotal += (float) $extra->amount;
            }

            $taxRate = 0.05;
            $tax = round($subtotal * $taxRate, 2);
            $discount = $quote ? (float) $quote->discount : 0.0;
            $total = max(0.0, round($subtotal + $tax - $discount, 2));

            // Select provider user id
            $providerId = $quote?->provider_id ?? $request->resolveProviderId() ?? $request->target_provider_id ?? $request->customer_id;

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'service_request_id' => $request->id,
                'customer_id' => $request->customer_id,
                'provider_id' => $providerId,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'tip_amount' => 0.00,
                'total' => $total,
                'status' => InvoiceStatus::Issued,
            ]);

            foreach ($itemsToCreate as $itemData) {
                InvoiceItem::create(array_merge($itemData, ['invoice_id' => $invoice->id]));
            }

            return $invoice;
        });
    }
}
