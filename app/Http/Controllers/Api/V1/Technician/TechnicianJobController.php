<?php

namespace App\Http\Controllers\Api\V1\Technician;

use App\Enums\InvoiceStatus;
use App\Enums\JobAssignmentStatus;
use App\Enums\NotificationType;
use App\Enums\QuoteStatus;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuoteResource;
use App\Models\Invoice;
use App\Models\JobAssignment;
use App\Models\JobInspection;
use App\Models\JobProgress;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Services\BusinessTime;
use App\Services\EmailNotificationService;
use App\Services\Invoice\InvoiceService;
use App\Services\Media\ImageUploadService;
use App\Services\PushNotificationService;
use App\Services\Quote\QuoteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $isOngoing = $request->boolean('ongoing') || $request->get('status') === 'ongoing';

        if ($isOngoing) {
            $query->whereIn('status', [
                JobAssignmentStatus::Assigned,
                JobAssignmentStatus::Accepted,
                JobAssignmentStatus::EnRoute,
                JobAssignmentStatus::Arrived,
                JobAssignmentStatus::Inspecting,
                JobAssignmentStatus::InProgress,
            ]);
        } elseif ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $dateFilter = $request->get('date_filter');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if ($dateFilter === 'today' || $request->boolean('today') || $request->get('date') === 'today') {
            $today = BusinessTime::today();
            [$startDateTime, $endDateTime] = BusinessTime::utcRangeForLocalDates($today, $today);
            $query->where(function ($q) use ($today, $startDateTime, $endDateTime, $isOngoing) {
                $q->whereBetween('assigned_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orWhereHas('serviceRequest', function ($sq) use ($today) {
                        $sq->whereDate('preferred_date', $today);
                    });

                if (! $isOngoing && request()->has('date_filter')) {
                    $q->orWhereBetween('completed_at', [$startDateTime, $endDateTime]);
                }

                if (! request()->has('date_filter')) {
                    $activeStatuses = [
                        JobAssignmentStatus::Assigned,
                        JobAssignmentStatus::Accepted,
                        JobAssignmentStatus::EnRoute,
                        JobAssignmentStatus::Arrived,
                        JobAssignmentStatus::Inspecting,
                        JobAssignmentStatus::InProgress,
                    ];
                    if (! $isOngoing) {
                        $activeStatuses[] = JobAssignmentStatus::Completed;
                    }
                    $q->orWhereIn('status', $activeStatuses);
                }
            });

            if ($isOngoing) {
                $query->whereNotIn('status', [
                    JobAssignmentStatus::Completed,
                    JobAssignmentStatus::Declined,
                ]);
            }
        } elseif ($dateFilter === 'this_week') {
            $startDay = BusinessTime::now()->startOfWeek()->toDateString();
            $endDay = BusinessTime::now()->endOfWeek()->toDateString();
            [$startDateTime, $endDateTime] = BusinessTime::utcRangeForLocalDates($startDay, $endDay);
            $query->where(function ($q) use ($startDateTime, $endDateTime, $startDay, $endDay) {
                $q->whereBetween('assigned_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('completed_at', [$startDateTime, $endDateTime])
                    ->orWhereHas('serviceRequest', function ($sq) use ($startDay, $endDay) {
                        $sq->whereBetween('preferred_date', [$startDay, $endDay]);
                    });
            });
        } elseif ($dateFilter === 'this_month') {
            $startDay = BusinessTime::now()->startOfMonth()->toDateString();
            $endDay = BusinessTime::now()->endOfMonth()->toDateString();
            [$startDateTime, $endDateTime] = BusinessTime::utcRangeForLocalDates($startDay, $endDay);
            $query->where(function ($q) use ($startDateTime, $endDateTime, $startDay, $endDay) {
                $q->whereBetween('assigned_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('completed_at', [$startDateTime, $endDateTime])
                    ->orWhereHas('serviceRequest', function ($sq) use ($startDay, $endDay) {
                        $sq->whereBetween('preferred_date', [$startDay, $endDay]);
                    });
            });
        } elseif (($dateFilter === 'custom' || $startDate || $endDate) && ($startDate || $endDate)) {
            $startDay = Carbon::parse($startDate ?: $endDate)->toDateString();
            $endDay = Carbon::parse($endDate ?: $startDate)->toDateString();
            [$startDateTime, $endDateTime] = BusinessTime::utcRangeForLocalDates($startDay, $endDay);
            $query->where(function ($q) use ($startDateTime, $endDateTime, $startDay, $endDay) {
                $q->whereBetween('assigned_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orWhereBetween('completed_at', [$startDateTime, $endDateTime])
                    ->orWhereHas('serviceRequest', function ($sq) use ($startDay, $endDay) {
                        $sq->whereBetween('preferred_date', [$startDay, $endDay]);
                    });
            });
        }

        $perPage = min((int) $request->get('per_page', 50), 100);
        $assignments = $query->orderBy('created_at', 'desc')->paginate($perPage);

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

        $assignment = JobAssignment::where('technician_id', $request->user()->id)
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
            ->first();

        if (! $assignment) {
            $assignment = JobAssignment::where('technician_id', $request->user()->id)
                ->whereIn('status', [
                    JobAssignmentStatus::Assigned,
                    JobAssignmentStatus::Accepted,
                    JobAssignmentStatus::EnRoute,
                    JobAssignmentStatus::Arrived,
                    JobAssignmentStatus::Inspecting,
                    JobAssignmentStatus::InProgress,
                ])
                ->latest()
                ->first();
        }

        if (! $assignment) {
            abort(404, 'Job assignment not found.');
        }

        return $assignment;
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
            'serviceRequest.invoices',
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

        $latestQuote = $assignment->serviceRequest?->quotes()->latest()->first();
        if ($latestQuote) {
            $latestQuote->load('items');
        }

        $assignmentData = $assignment->toArray();
        if ($latestQuote) {
            $assignmentData['latest_quote'] = (new QuoteResource($latestQuote))->resolve();
        }

        return response()->json([
            'success' => true,
            'message' => 'Job assignment details retrieved.',
            'data' => $assignmentData,
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

        $serviceRequest = $assignment->serviceRequest;
        if ($serviceRequest && $serviceRequest->customer_id) {
            app(PushNotificationService::class)->send(
                userId: $serviceRequest->customer_id,
                title: 'Job Accepted',
                body: 'Your technician has accepted your service request.',
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_job_accepted_cust_'.$assignment->id
            );
        }

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

        $serviceRequest = $assignment->serviceRequest;
        if ($serviceRequest && $serviceRequest->customer_id) {
            app(PushNotificationService::class)->send(
                userId: $serviceRequest->customer_id,
                title: 'Technician Arrived',
                body: 'Your technician has arrived at your location.',
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_job_arrived_cust_'.$assignment->id
            );
        }

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

        // Issue the final invoice now so the customer can pay the remaining balance online.
        $completedRequest = $assignment->serviceRequest;
        if ($completedRequest && ! $completedRequest->invoices()->exists()) {
            $approvedQuote = $completedRequest->quotes()->where('status', QuoteStatus::Approved)->latest()->first()
                ?? $completedRequest->commercialQuote;
            $this->invoiceService->generateInvoice($completedRequest, $approvedQuote);
        }

        if (! empty($photoPaths)) {
            JobProgress::create([
                'service_request_id' => $assignment->service_request_id,
                'technician_id' => $request->user()->id,
                'progress_percentage' => 100,
                'description' => 'Job completed post-work proof photos.',
                'photos' => $photoPaths,
            ]);
        }

        $serviceRequest = $assignment->serviceRequest;
        if ($serviceRequest) {
            // Customer Notification
            app(PushNotificationService::class)->send(
                userId: $serviceRequest->customer_id,
                title: 'Job Completed',
                body: 'Your service request has been completed.',
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_job_completed_cust_'.$assignment->id
            );

            // Technician Notification
            app(PushNotificationService::class)->send(
                userId: $assignment->technician_id,
                title: 'Job Completed',
                body: 'The assigned job has been marked as completed.',
                type: NotificationType::Booking->value,
                data: [
                    'type' => 'booking',
                    'booking_id' => $serviceRequest->request_number,
                    'screen' => 'booking_details',
                ],
                eventId: $serviceRequest->request_number.'_job_completed_tech_'.$assignment->id
            );

            app(EmailNotificationService::class)->sendBookingCompleted(
                $serviceRequest,
                $assignment
            );
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
        $assignment->load(['serviceRequest.quotes', 'provider']);

        // Prevent duplicate pending quote creation
        $existingQuote = $assignment->serviceRequest?->quotes()->latest()->first();
        if ($existingQuote && $existingQuote->status === QuoteStatus::Pending) {
            return response()->json([
                'success' => true,
                'message' => 'A quotation has already been submitted and is currently awaiting approval.',
                'data' => new QuoteResource($existingQuote->load('items')),
            ], 200);
        }

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

        $assignment->update(['status' => JobAssignmentStatus::Inspecting]);

        if ($provider) {
            try {
                app(PushNotificationService::class)->send(
                    userId: $provider->id,
                    title: 'Commercial Quotation Submitted',
                    body: "Technician {$technician->name} has submitted a quotation ($".number_format((float) $quote->total, 2).") for request #{$assignment->serviceRequest?->request_number}. Please review and approve.",
                    type: NotificationType::Booking->value,
                    data: [
                        'type' => 'quote_submitted',
                        'quote_id' => $quote->id,
                        'request_id' => $assignment->serviceRequest?->id,
                        'screen' => 'request_details',
                    ],
                    eventId: $assignment->serviceRequest?->request_number.'_quote_submitted_'.$quote->id
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to send quote notification to provider: '.$e->getMessage());
            }
        }

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
                'deposit_paid' => $serviceRequest->depositPaidAmount(),
                'balance_due' => $invoice->status === InvoiceStatus::Paid
                    ? 0.0
                    : max(0.0, round((float) $invoice->total - (float) $invoice->tip_amount - $serviceRequest->depositPaidAmount(), 2)),
                'is_paid' => $invoice->status === InvoiceStatus::Paid,
                'payment_status' => $invoice->status === InvoiceStatus::Paid ? 'paid' : 'awaiting_customer_payment',
            ],
        ]);
    }

    /**
     * Download PDF invoice for assigned job.
     */
    public function downloadInvoice(Request $request, string|int $assignmentId)
    {
        $assignment = $this->findAssignment($request, $assignmentId);
        $serviceRequest = $assignment->serviceRequest;

        if (! $serviceRequest) {
            return response()->json(['success' => false, 'message' => 'Service request not found.'], 404);
        }

        $serviceRequest->load([
            'category',
            'subCategory',
            'service',
            'customer',
            'targetProvider',
            'jobAssignments.technician',
            'quotes.items',
            'invoices.items',
        ]);

        $customer = $serviceRequest->customer;
        $provider = $serviceRequest->targetProvider;
        $technician = $request->user();
        $quote = $serviceRequest->quotes()->latest()->first();
        $invoice = $serviceRequest->invoices()->latest()->first();

        $invoiceNumber = $invoice?->invoice_number ?? 'INV-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT);
        $requestNumber = $serviceRequest->request_number ?? 'SR-'.$serviceRequest->id;

        $items = [];
        $subtotal = 0.0;
        $tax = 0.0;
        $discount = 0.0;
        $total = 0.0;

        if ($invoice && $invoice->items->isNotEmpty()) {
            foreach ($invoice->items as $invItem) {
                $amt = (float) $invItem->amount;
                $items[] = [
                    'description' => $invItem->description,
                    'type' => 'Service',
                    'unit_price' => $amt,
                    'total_price' => $amt,
                ];
            }
            $subtotal = (float) $invoice->subtotal;
            $tax = (float) $invoice->tax;
            $discount = (float) $invoice->discount;
            $total = (float) $invoice->total;
        } elseif ($quote && $quote->items->isNotEmpty()) {
            foreach ($quote->items as $qItem) {
                $items[] = [
                    'description' => $qItem->description,
                    'type' => $qItem->type ?? 'Service',
                    'unit_price' => (float) $qItem->unit_price,
                    'total_price' => (float) $qItem->total_price,
                ];
            }
            $subtotal = (float) $quote->labor_subtotal + (float) $quote->material_subtotal;
            $tax = (float) $quote->tax;
            $discount = (float) $quote->discount;
            $total = (float) $quote->total;
        } else {
            $basePrice = (float) ($serviceRequest->service?->base_price ?? 100.00);
            $items[] = [
                'description' => $serviceRequest->service?->name ?? $serviceRequest->description ?? 'Service Request Standard Package',
                'type' => 'Service',
                'unit_price' => $basePrice,
                'total_price' => $basePrice,
            ];
            $subtotal = $basePrice;
            $tax = round($subtotal * 0.05, 2);
            $total = $subtotal + $tax;
        }

        if ($tax == 0.0 && $subtotal > 0) {
            $tax = round($subtotal * 0.05, 2);
        }

        $tipAmount = (float) ($invoice?->tip_amount ?? 0);
        if ($invoice && (float) $invoice->total > 0) {
            $total = (float) $invoice->total;
        } else {
            $total = round($subtotal + $tax - $discount + $tipAmount, 2);
        }
        $depositPaid = min($serviceRequest->depositPaidAmount(), $total);

        $pdfData = [
            'invoiceNumber' => $invoiceNumber,
            'requestNumber' => $requestNumber,
            'dateIssued' => BusinessTime::now($serviceRequest->localTimezone())->format('M d, Y'),
            'customerName' => $customer?->name ?? 'Valued Customer',
            'customerEmail' => $customer?->email ?? 'customer@example.com',
            'customerPhone' => $customer?->phone ?? 'N/A',
            'serviceAddress' => $serviceRequest->address_line ?? 'Customer Address',
            'providerName' => $provider?->name ?? 'Deluxe Home Care Services',
            'technicianName' => $technician?->name ?? 'Assigned Technician',
            'serviceTitle' => $serviceRequest->service?->name ?? $serviceRequest->description ?? 'On-Demand Service',
            'categoryName' => $serviceRequest->category?->name ?? 'General Maintenance',
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'tipAmount' => $tipAmount,
            'total' => $total,
            'depositPaid' => $depositPaid,
        ];

        $pdf = Pdf::loadView('invoices.pdf', $pdfData);
        $filename = "Invoice-{$requestNumber}.pdf";

        if ($request->boolean('inline') || $request->get('disposition') === 'inline') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    /**
     * Get technician's rating summary and star breakdown.
     */
    public function getRatingSummary(Request $request): JsonResponse
    {
        $technician = $request->user();
        if (! $technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized actor.'], 403);
        }

        $reviews = Review::where('technician_id', $technician->id)->get();
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round((float) $reviews->avg('rating'), 1) : 0.0;

        $starCounts = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Rating summary retrieved.',
            'data' => [
                'average_rating' => (float) $averageRating,
                'total_reviews' => (int) $totalReviews,
                'star_counts' => $starCounts,
            ],
        ]);
    }
}
