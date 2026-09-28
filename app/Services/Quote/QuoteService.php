<?php

namespace App\Services\Quote;

use App\Enums\ApprovalType;
use App\Enums\JobAssignmentStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Models\CustomerApproval;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuoteService
{
    /**
     * Create commercial quote for a service request based on server-recalculated line items.
     */
    public function createQuote(ServiceRequest $request, User $provider, ?User $technician, array $items, float $taxRate = 0.05, float $discount = 0.00, ?string $notes = null): Quote
    {
        return DB::transaction(function () use ($request, $provider, $technician, $items, $taxRate, $discount, $notes) {
            $laborSubtotal = 0.0;
            $materialSubtotal = 0.0;
            $processedItems = [];

            foreach ($items as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $totalPrice = round($qty * $unitPrice, 2);

                if ($item['type'] === 'labor') {
                    $laborSubtotal += $totalPrice;
                } else {
                    $materialSubtotal += $totalPrice;
                }

                $processedItems[] = [
                    'description' => $item['description'],
                    'type' => $item['type'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];
            }

            $subtotal = $laborSubtotal + $materialSubtotal;
            $tax = round($subtotal * $taxRate, 2);
            $grandTotal = max(0.0, round($subtotal + $tax - $discount, 2));

            $isApprovedByProvider = $technician === null || $technician->id === $provider->id;

            $quote = Quote::create([
                'service_request_id' => $request->id,
                'provider_id' => $provider->id,
                'technician_id' => $technician?->id,
                'labor_subtotal' => $laborSubtotal,
                'material_subtotal' => $materialSubtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $grandTotal,
                'notes' => $notes,
                'status' => QuoteStatus::Pending,
                'is_approved_by_provider' => $isApprovedByProvider,
                'provider_approved_at' => $isApprovedByProvider ? now() : null,
            ]);

            foreach ($processedItems as $pItem) {
                QuoteItem::create(array_merge($pItem, ['quote_id' => $quote->id]));
            }

            return $quote;
        });
    }

    /**
     * Provider updates line-items, sub-totals, and approval status for a quotation.
     */
    public function updateQuoteByProvider(User $provider, Quote $quote, array $items, float $taxRate = 0.05, float $discount = 0.00, ?string $notes = null, bool $approve = false): Quote
    {
        if ($quote->provider_id !== $provider->id) {
            throw new InvalidArgumentException('Provider is not authorized to edit this quotation.');
        }

        return DB::transaction(function () use ($quote, $items, $taxRate, $discount, $notes, $approve) {
            $laborSubtotal = 0.0;
            $materialSubtotal = 0.0;
            $processedItems = [];

            foreach ($items as $item) {
                $qty = (float) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $totalPrice = round($qty * $unitPrice, 2);
                $type = in_array($item['type'] ?? 'material', ['labor', 'material', 'additional_service'])
                    ? $item['type']
                    : 'material';

                if ($type === 'labor') {
                    $laborSubtotal += $totalPrice;
                } else {
                    $materialSubtotal += $totalPrice;
                }

                $processedItems[] = [
                    'description' => $item['description'] ?? 'Service Item',
                    'type' => $type,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];
            }

            $subtotal = $laborSubtotal + $materialSubtotal;
            $tax = round($subtotal * $taxRate, 2);
            $grandTotal = max(0.0, round($subtotal + $tax - $discount, 2));

            $updateData = [
                'labor_subtotal' => $laborSubtotal,
                'material_subtotal' => $materialSubtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $grandTotal,
                'notes' => $notes ?? $quote->notes,
            ];

            if ($approve) {
                $updateData['is_approved_by_provider'] = true;
                $updateData['provider_approved_at'] = now();
            }

            $quote->update($updateData);

            // Re-sync quote items
            $quote->items()->delete();
            foreach ($processedItems as $pItem) {
                QuoteItem::create(array_merge($pItem, ['quote_id' => $quote->id]));
            }

            return $quote->fresh(['items', 'serviceRequest']);
        });
    }

    /**
     * Provider approves technician-submitted quotation.
     */
    public function approveByProvider(User $provider, Quote $quote): Quote
    {
        if ($quote->provider_id !== $provider->id) {
            throw new InvalidArgumentException('Provider is not authorized to approve this quotation.');
        }

        $quote->update([
            'is_approved_by_provider' => true,
            'provider_approved_at' => now(),
        ]);

        return $quote->fresh();
    }

    /**
     * Customer explicitly approves commercial quote.
     */
    public function approveQuote(User $customer, Quote $quote): CustomerApproval
    {
        if ($quote->serviceRequest->customer_id !== $customer->id) {
            throw new InvalidArgumentException('Customer is not authorized to approve this quote.');
        }

        if (! $quote->is_approved_by_provider) {
            throw new InvalidArgumentException('Quote must be approved by provider before customer approval.');
        }

        return DB::transaction(function () use ($customer, $quote) {
            $quote->update(['status' => QuoteStatus::Approved]);

            $approval = CustomerApproval::create([
                'customer_id' => $customer->id,
                'service_request_id' => $quote->service_request_id,
                'quote_id' => $quote->id,
                'approval_type' => ApprovalType::InitialQuote,
                'status' => 'approved',
                'approved_at' => now(),
            ]);

            // Update ServiceRequest status to in progress
            $quote->serviceRequest->update(['status' => ServiceRequestStatus::InProgress]);

            // Also update active JobAssignment status to in_progress
            $quote->serviceRequest->jobAssignments()
                ->whereNotIn('status', [JobAssignmentStatus::Completed, JobAssignmentStatus::Declined])
                ->update(['status' => JobAssignmentStatus::InProgress]);

            return $approval;
        });
    }
}
