<?php

namespace App\Http\Controllers\Provider;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ProviderEarning;
use App\Models\ProviderProfile;
use App\Services\BusinessTime;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderEarningController extends Controller
{
    /**
     * Display dynamic earnings statement strictly scoped to the authenticated provider.
     */
    public function index(Request $request): View
    {
        $providerId = $request->user()->id;

        // Auto-sync any paid invoices for this provider that might not have a ProviderEarning record yet or have misassigned provider_id
        $paidInvoices = Invoice::where('status', InvoiceStatus::Paid)
            ->where(function ($q) use ($providerId) {
                $q->where('provider_id', $providerId)
                    ->orWhereHas('serviceRequest', function ($sq) use ($providerId) {
                        $sq->where('target_provider_id', $providerId)
                            ->orWhereHas('jobAssignments', function ($jaQ) use ($providerId) {
                                $jaQ->where('provider_id', $providerId);
                            })
                            ->orWhereHas('quotes', function ($qQ) use ($providerId) {
                                $qQ->where('provider_id', $providerId);
                            });
                    });
            })
            ->get();

        $providerProfile = ProviderProfile::where('user_id', $providerId)->first();
        $commissionRate = $providerProfile ? ((float) $providerProfile->commission / 100) : 0.10;

        foreach ($paidInvoices as $invoice) {
            if ($invoice->provider_id !== $providerId) {
                $invoice->update(['provider_id' => $providerId]);
            }

            $earning = ProviderEarning::where('invoice_id', $invoice->id)->first();

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

            if ($earning) {
                if ($earning->provider_id !== $providerId) {
                    $earning->update([
                        'provider_id' => $providerId,
                        'gross_amount' => $grandTotal + $tips,
                        'platform_fee' => $platformFee,
                        'net_amount' => $netAmount,
                    ]);
                }
            } else {
                ProviderEarning::create([
                    'provider_id' => $providerId,
                    'service_request_id' => $invoice->service_request_id,
                    'invoice_id' => $invoice->id,
                    'gross_amount' => $grandTotal + $tips,
                    'platform_fee' => $platformFee,
                    'net_amount' => $netAmount,
                ]);
            }
        }

        $query = ProviderEarning::where('provider_id', $providerId);

        // Date Preset Filtering
        $preset = $request->get('preset', 'all');
        $localNow = BusinessTime::now();
        $range = match ($preset) {
            'today' => [$localNow->toDateString(), $localNow->toDateString()],
            'this_week' => [$localNow->copy()->startOfWeek()->toDateString(), $localNow->copy()->endOfWeek()->toDateString()],
            'this_month' => [$localNow->copy()->startOfMonth()->toDateString(), $localNow->copy()->endOfMonth()->toDateString()],
            'last_month' => [
                $localNow->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $localNow->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => null,
        };

        if ($preset === 'all') {
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $range = ($fromDate || $toDate) ? [$fromDate ?: '1970-01-01', $toDate ?: '2999-12-31'] : null;
        }

        if ($range) {
            $query->whereBetween('created_at', BusinessTime::utcRangeForLocalDates($range[0], $range[1]));
        }

        // Search Filter
        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('serviceRequest', function ($sq) use ($search) {
                    $sq->where('request_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%");
                        });
                });
            });
        }

        $totalGross = (float) (clone $query)->sum('gross_amount');
        $totalPlatformFee = (float) (clone $query)->sum('platform_fee');
        $totalNet = (float) (clone $query)->sum('net_amount');
        $totalPaidJobs = (int) (clone $query)->count();
        $avgEarning = $totalPaidJobs > 0 ? ($totalNet / $totalPaidJobs) : 0.00;

        $earnings = $query->with(['serviceRequest.customer', 'invoice'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('provider.earnings.index', compact(
            'earnings',
            'totalGross',
            'totalPlatformFee',
            'totalNet',
            'totalPaidJobs',
            'avgEarning',
            'preset'
        ));
    }
}
