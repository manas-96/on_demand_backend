<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\SystemSetting;
use App\Services\Invoice\InvoiceService;
use App\Services\Media\ImageUploadService;
use App\Services\Quote\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TechnicianJobController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService,
        protected ImageUploadService $uploadService,
        protected InvoiceService $invoiceService
    ) {}

    /**
     * Get technician's assigned jobs.
     */
    public function myJobs(Request $request): JsonResponse
    {
        $technician = $request->user();
        if (! $technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $query = JobAssignment::with([
            'serviceRequest.category',
            'serviceRequest.subCategory',
            'serviceRequest.service',
            'serviceRequest.customer',
            'serviceRequest.photos',
            'serviceRequest.quotes.items',
            'serviceRequest.invoices',
            'provider',
        ])
            ->where('technician_id', $technician->id);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('today') || $request->get('date') === 'today') {
            $today = now()->format('Y-m-d');
            $query->where(function ($q) use ($today) {
                $q->whereDate('assigned_at', $today)
                    ->orWhereDate('created_at', $today)
                    ->orWhereDate('completed_at', $today)
                    ->orWhereHas('serviceRequest', function ($sq) use ($today) {
                        $sq->whereDate('preferred_date', $today);
                    })
                    ->orWhereIn('status', [
                        JobAssignmentStatus::Accepted,
                        JobAssignmentStatus::Arrived,
                        JobAssignmentStatus::Inspecting,
                        JobAssignmentStatus::InProgress,
                        JobAssignmentStatus::Completed,
                    ]);
            });
        }

        $assignments = $query->orderBy('created_at', 'desc')->paginate(15);

        foreach ($assignments as $assignment) {
            if ($assignment->serviceRequest && $assignment->serviceRequest->status === ServiceRequestStatus::InProgress && $assignment->status !== JobAssignmentStatus::Completed) {
                if ($assignment->status !== JobAssignmentStatus::InProgress) {
                    $assignment->update(['status' => JobAssignmentStatus::InProgress]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Assigned jobs retrieved.',
            'data' => $assignments,
        ]);
    }

    /**
     * Helper to find job assignment by ID, service_request_id, or request_number.
     */
    protected function findAssignment(Request $request, string|int $assignmentId): JobAssignment
    {
        $rawId = trim((string) $assignmentId);
        $cleanId = preg_replace('/[^0-9]/', '', $rawId);

        return JobAssignment::where('technician_id', $request->user()->id)
            ->where(function ($query) use ($rawId, $cleanId) {
                if ($rawId !== '' && is_numeric($rawId)) {
                    $query->where('id', (int) $rawId)
                        ->orWhere('service_request_id', (int) $rawId);
                }
                if ($cleanId !== '') {
                    $query->orWhere('id', (int) $cleanId)
                        ->orWhere('service_request_id', (int) $cleanId);
                }
                $query->orWhereHas('serviceRequest', function ($sq) use ($rawId, $cleanId) {
                    $sq->where('request_number', $rawId)
                        ->orWhere('request_number', 'LIKE', '%'.$rawId.'%');
                    if ($cleanId !== '') {
                        $sq->orWhere('request_number', 'LIKE', '%'.$cleanId.'%')
                            ->orWhereRaw("REPLACE(REPLACE(request_number, '-', ''), 'SR', '') LIKE ?", ["%{$cleanId}%"]);
                    }
                });
            })
            ->firstOrFail();
    }

    /**
     * Get details for a single assigned job.
     */
    public function show(Request $request, string|int $assignmentId): JsonResponse
    {
        $technician = $request->user();
        if (! $technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $assignment = $this->findAssignment($request, $assignmentId);
        $assignment->load([
            'serviceRequest.category',
            'serviceRequest.subCategory',
            'serviceRequest.service',
            'serviceRequest.customer',
            'serviceRequest.photos',
            'serviceRequest.quotes.items',
            'provider',
            'technician',
            'jobInspections',
            'jobProgresses',
        ]);

        if ($assignment->serviceRequest && $assignment->serviceRequest->status === ServiceRequestStatus::InProgress && $assignment->status !== JobAssignmentStatus::Completed) {
            if ($assignment->status !== JobAssignmentStatus::InProgress) {
                $assignment->update(['status' => JobAssignmentStatus::InProgress]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Job assignment details retrieved.',
            'data' => $assignment,
        ]);
    }

    /**
     * Accept job assignment.
     */
    public function acceptJob(Request $request, string|int $assignmentId): JsonResponse
    {
        $assignment = $this->findAssignment($request, $assignmentId);
        $assignment->update([
            'status' => JobAssignmentStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Job assignment accepted.',
            'data' => $assignment,
        ]);
    }

    /**
     * Mark arrived on-site.
     */
    public function markArrived(Request $request, string|int $assignmentId): JsonResponse
    {
        $assignment = $this->findAssignment($request, $assignmentId);
        $assignment->update([
            'status' => JobAssignmentStatus::Arrived,
            'arrived_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Arrived on site logged.',
            'data' => $assignment,
        ]);
    }

    /**
     * Submit physical inspection findings.
     */
    public function submitInspection(Request $request, string|int $assignmentId): JsonResponse
    {
        $request->validate([
            'technical_notes' => ['required', 'string'],
            'estimated_labor_hours' => ['required', 'numeric', 'min:0'],
            'estimated_material_cost' => ['required', 'numeric', 'min:0'],
            'photos' => ['nullable'],
        ]);

        $assignment = $this->findAssignment($request, $assignmentId);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            $photoPaths = $this->uploadService->uploadMultiple($files, 'inspections', 'public');
        } elseif (! empty($request->photos) && is_array($request->photos)) {
            foreach ($request->photos as $photoItem) {
                if (is_string($photoItem) && trim($photoItem) !== '') {
                    $str = trim($photoItem);
                    if (! Str::startsWith($str, ['/data/', '/var/', 'data/', 'var/'])) {
                        $photoPaths[] = $str;
                    }
                }
            }
        }

        $inspection = JobInspection::create([
            'service_request_id' => $assignment->service_request_id,
            'job_assignment_id' => $assignment->id,
            'technician_id' => $request->user()->id,
            'technical_notes' => $request->technical_notes,
            'estimated_labor_hours' => $request->estimated_labor_hours,
            'estimated_material_cost' => $request->estimated_material_cost,
            'photos' => $photoPaths,
        ]);

        $assignment->update(['status' => JobAssignmentStatus::Inspecting]);

        return response()->json([
            'success' => true,
            'message' => 'Inspection findings submitted successfully.',
            'data' => $inspection,
        ]);
    }

    /**
     * Update job progress percentage (0-100%).
     */
    public function updateProgress(Request $request, string|int $assignmentId): JsonResponse
    {
        $request->validate([
            'progress_percentage' => ['required', 'integer', 'between:0,100'],
            'description' => ['nullable', 'string'],
            'photos' => ['nullable'],
        ]);

        $assignment = $this->findAssignment($request, $assignmentId);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            $photoPaths = $this->uploadService->uploadMultiple($files, 'progress', 'public');
        } elseif (! empty($request->photos) && is_array($request->photos)) {
            foreach ($request->photos as $photoItem) {
                if (is_string($photoItem) && trim($photoItem) !== '') {
                    $str = trim($photoItem);
                    if (! Str::startsWith($str, ['/data/', '/var/', 'data/', 'var/'])) {
                        $photoPaths[] = $str;
                    }
                }
            }
        }

        $progress = JobProgress::create([
            'service_request_id' => $assignment->service_request_id,
            'technician_id' => $request->user()->id,
            'progress_percentage' => $request->progress_percentage,
            'description' => $request->description,
            'photos' => $photoPaths,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Progress updated.',
            'data' => $progress,
        ]);
    }

    /**
     * Mark job complete.
     */
    public function completeJob(Request $request, string|int $assignmentId): JsonResponse
    {
        $assignment = $this->findAssignment($request, $assignmentId);

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            $photoPaths = $this->uploadService->uploadMultiple($files, 'completion', 'public');
        } elseif (! empty($request->photos) && is_array($request->photos)) {
            foreach ($request->photos as $photoItem) {
                if (is_string($photoItem) && trim($photoItem) !== '') {
                    $str = trim($photoItem);
                    if (! Str::startsWith($str, ['/data/', '/var/', 'data/', 'var/'])) {
                        $photoPaths[] = $str;
                    }
                }
            }
        }

        $assignment->update([
            'status' => JobAssignmentStatus::Completed,
            'completed_at' => now(),
        ]);

        $assignment->serviceRequest?->update([
            'status' => ServiceRequestStatus::Completed,
        ]);

        if (! empty($photoPaths)) {
            JobProgress::create([
                'service_request_id' => $assignment->service_request_id,
                'technician_id' => $request->user()->id,
                'progress_percentage' => 100,
                'description' => 'Job completed post-work proof photos.',
                'photos' => $photoPaths,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job marked as completed.',
            'data' => $assignment->fresh(),
        ]);
    }

    /**
     * Technician uploads commercial quote for assigned job (sent to provider for approval).
     */
    public function submitQuote(Request $request, string|int $assignmentId): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'string', 'in:labor,material,additional_service'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $technician = $request->user();
        $assignment = $this->findAssignment($request, $assignmentId);
        $assignment->load(['serviceRequest', 'provider']);

        $provider = $assignment->provider;

        $systemTaxDecimal = round((float) SystemSetting::getSettings()->tax_rate / 100, 4);
        $rawTaxRate = $request->tax_rate;
        $taxRate = $rawTaxRate !== null
            ? ((float) $rawTaxRate > 1.0 ? round((float) $rawTaxRate / 100, 4) : (float) $rawTaxRate)
            : $systemTaxDecimal;

        $quote = $this->quoteService->createQuote(
            request: $assignment->serviceRequest,
            provider: $provider,
            technician: $technician,
            items: $request->items,
            taxRate: $taxRate,
            discount: (float) ($request->discount ?? 0.00),
            notes: $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => 'Quotation uploaded successfully. Sent to provider for approval.',
            'data' => new QuoteResource($quote->load('items')),
        ], 201);
    }

    /**
     * Check quote approval status for an assigned job.
     */
    public function checkQuoteStatus(Request $request, string|int $assignmentId): JsonResponse
    {
        $assignment = $this->findAssignment($request, $assignmentId)->load(['serviceRequest.quotes']);
        $quote = $assignment->serviceRequest?->quotes()->latest()->first();

        if (! $quote) {
            return response()->json([
                'success' => true,
                'message' => 'No quotation found for this job.',
                'data' => [
                    'has_quote' => false,
                    'is_approved_by_provider' => false,
                    'is_approved_by_customer' => false,
                    'is_fully_approved' => false,
                    'approval_stage' => 'pending_quote',
                ],
            ]);
        }

        $isApprovedByProvider = (bool) $quote->is_approved_by_provider;
        $isApprovedByCustomer = $quote->status === QuoteStatus::Approved;
        $isFullyApproved = $isApprovedByProvider && $isApprovedByCustomer;

        $approvalStage = match (true) {
            $quote->status === QuoteStatus::Rejected => 'rejected',
            ! $isApprovedByProvider => 'pending_provider',
            ! $isApprovedByCustomer => 'pending_customer',
            default => 'fully_approved',
        };

        return response()->json([
            'success' => true,
            'message' => 'Quote status retrieved.',
            'data' => [
                'has_quote' => true,
                'quote_id' => $quote->id,
                'status' => $quote->status->value,
                'is_approved_by_provider' => $isApprovedByProvider,
                'is_approved_by_customer' => $isApprovedByCustomer,
                'is_fully_approved' => $isFullyApproved,
                'approval_stage' => $approvalStage,
                'total_amount' => (float) $quote->total,
                'provider_approved_at' => $quote->provider_approved_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get technician's current online/offline duty status.
     */
    public function getDutyStatus(Request $request): JsonResponse
    {
        $technician = $request->user();
        $profile = $technician->technicianProfile;

        $dutyStatus = $profile?->duty_status ?? 'online';
        $isOnline = $profile ? ($dutyStatus === 'online' || $profile->is_active) : true;

        return response()->json([
            'success' => true,
            'message' => 'Duty status retrieved.',
            'data' => [
                'is_online' => $isOnline,
                'duty_status' => $dutyStatus,
            ],
        ]);
    }

    /**
     * Toggle technician's online/offline duty status.
     */
    public function toggleDutyStatus(Request $request): JsonResponse
    {
        $request->validate([
            'is_online' => ['nullable', 'boolean'],
            'duty_status' => ['nullable', 'string', 'in:online,offline'],
        ]);

        $technician = $request->user();
        $profile = $technician->technicianProfile;

        $dutyStatus = $request->get('duty_status');
        if (! $dutyStatus && $request->has('is_online')) {
            $dutyStatus = $request->boolean('is_online') ? 'online' : 'offline';
        }
        $dutyStatus = $dutyStatus ?? 'online';
        $isOnline = $dutyStatus === 'online';

        if ($profile) {
            $profile->update([
                'duty_status' => $dutyStatus,
                'is_active' => $isOnline,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Duty status updated to {$dutyStatus}.",
            'data' => [
                'is_online' => $isOnline,
                'duty_status' => $dutyStatus,
            ],
        ]);
    }

    /**
     * Update technician's live location.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $technician = $request->user();
        if (! $technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $profile = $technician->technicianProfile;
        if ($profile) {
            $profile->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'last_location_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => [
                'latitude' => (float) $request->latitude,
                'longitude' => (float) $request->longitude,
                'updated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get or generate authoritative final invoice breakdown for an assigned job.
     */
    public function getInvoice(Request $request, string|int $assignmentId): JsonResponse
    {
        $assignment = $this->findAssignment($request, $assignmentId);
        $assignment->load([
            'serviceRequest.category',
            'serviceRequest.subCategory',
            'serviceRequest.service',
            'serviceRequest.quotes.items',
            'serviceRequest.invoices.items',
            'provider',
            'technician',
        ]);

        $serviceRequest = $assignment->serviceRequest;
        if (! $serviceRequest) {
            return response()->json(['success' => false, 'message' => 'Service request not found.'], 404);
        }

        $invoice = $serviceRequest->invoices()->latest()->first();

        if (! $invoice) {
            $quote = $serviceRequest->quotes()->where('status', QuoteStatus::Approved)->latest()->first()
                ?? $serviceRequest->quotes()->latest()->first();

            $invoice = $this->invoiceService->generateInvoice($serviceRequest, $quote);
            $invoice->load('items');
        } else {
            $invoice->load('items');
        }

        $items = [];
        if ($invoice->items->isNotEmpty()) {
            foreach ($invoice->items as $item) {
                $items[] = [
                    'id' => (string) $item->id,
                    'description' => $item->description,
                    'amount' => (float) $item->amount,
                    'is_secondary' => Str::contains(strtolower($item->description), ['part', 'material', 'tax', 'extra']),
                ];
            }
        } else {
            $quote = $serviceRequest->quotes()->latest()->first();
            if ($quote) {
                foreach ($quote->items as $qItem) {
                    $items[] = [
                        'id' => (string) $qItem->id,
                        'description' => $qItem->description,
                        'amount' => (float) $qItem->total_price,
                        'is_secondary' => $qItem->type !== 'labor',
                    ];
                }
            }
        }

        $subtotal = (float) $invoice->subtotal;
        if ($subtotal == 0.0 && ! empty($items)) {
            $subtotal = (float) array_sum(array_column($items, 'amount'));
        }

        $tax = (float) $invoice->tax;
        if ($tax == 0.0 && $subtotal > 0) {
            $tax = round($subtotal * 0.05, 2);
        }

        $discount = (float) $invoice->discount;
        $tipAmount = (float) ($invoice->tip_amount ?? 0.0);
        $baseTotal = max(0.0, round($subtotal + $tax - $discount, 2));
        $grandTotal = $baseTotal + $tipAmount;

        $laborCost = 0.0;
        $materialsSubtotal = 0.0;
        foreach ($items as $it) {
            if (Str::contains(strtolower($it['description']), ['labor', 'service'])) {
                $laborCost += $it['amount'];
            } else {
                $materialsSubtotal += $it['amount'];
            }
        }

        if ($laborCost == 0.0) {
            $laborCost = $subtotal;
        }

        return response()->json([
            'success' => true,
            'message' => 'Final invoice details retrieved successfully.',
            'data' => [
                'id' => (string) $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'job_id' => $assignment->serviceRequest?->request_number ?? 'JOB-'.$assignment->id,
                'status' => $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'tip_amount' => $tipAmount,
                'base_total' => $baseTotal,
                'grand_total' => $grandTotal,
                'labor_cost' => $laborCost,
                'materials_subtotal' => $materialsSubtotal,
                'items' => $items,
            ],
        ]);
    }

    /**
     * Settle final invoice payment and collect payout.
     */
    public function settleInvoice(Request $request, string|int $assignmentId): JsonResponse
    {
        $request->validate([
            'tip_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
        ]);

        $assignment = $this->findAssignment($request, $assignmentId);
        $serviceRequest = $assignment->serviceRequest;

        if (! $serviceRequest) {
            return response()->json(['success' => false, 'message' => 'Service request not found.'], 404);
        }

        $invoice = $serviceRequest->invoices()->latest()->first();

        if (! $invoice) {
            $quote = $serviceRequest->quotes()->latest()->first();
            $invoice = $this->invoiceService->generateInvoice($serviceRequest, $quote);
        }

        $tipAmount = (float) ($request->tip_amount ?? 0.0);
        $subtotal = (float) $invoice->subtotal;
        if ($subtotal == 0.0 && $invoice->items->isNotEmpty()) {
            $subtotal = (float) $invoice->items->sum('amount');
        }

        $tax = (float) $invoice->tax;
        if ($tax == 0.0 && $subtotal > 0) {
            $tax = round($subtotal * 0.05, 2);
        }

        $discount = (float) $invoice->discount;
        $grandTotal = max(0.0, round($subtotal + $tax - $discount + $tipAmount, 2));

        $invoice->update([
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tip_amount' => $tipAmount,
            'total' => $grandTotal,
            'status' => InvoiceStatus::Paid,
        ]);

        $assignment->update([
            'status' => JobAssignmentStatus::Completed,
            'completed_at' => now(),
        ]);

        $serviceRequest->update([
            'status' => ServiceRequestStatus::Completed,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invoice payment settled and credited to wallet.',
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => 'paid',
                'tip_amount' => $tipAmount,
                'grand_payout' => $grandTotal,
                'settled_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
