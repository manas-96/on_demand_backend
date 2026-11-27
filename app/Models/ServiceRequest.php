<?php

namespace App\Models;

use App\Enums\JobAssignmentStatus;
use App\Enums\RequestType;
use App\Enums\ServiceRequestStatus;
use App\Services\BusinessTime;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class ServiceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number',
        'customer_id',
        'service_category_id',
        'sub_category_id',
        'service_id',
        'request_type',
        'target_provider_id',
        'description',
        'address_line',
        'latitude',
        'longitude',
        'preferred_date',
        'preferred_start_time',
        'preferred_end_time',
        'priority',
        'status',
        'deposit_amount',
        'deposit_paid_at',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'preferred_date' => 'date:Y-m-d',
            'request_type' => RequestType::class,
            'status' => ServiceRequestStatus::class,
            'deposit_amount' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (ServiceRequest $serviceRequest) {
            foreach ($serviceRequest->photos as $photo) {
                $photo->delete();
            }
        });
    }

    /**
     * Generate unique service request number.
     */
    public static function generateRequestNumber(): string
    {
        $datePrefix = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return sprintf('SR-%s-%06d', $datePrefix, $count);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'sub_category_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function targetProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_provider_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RequestPhoto::class);
    }

    public function providerJobRequests(): HasMany
    {
        return $this->hasMany(ProviderJobRequest::class);
    }

    public function jobAssignment(): HasOne
    {
        return $this->hasOne(JobAssignment::class);
    }

    public function jobAssignments(): HasMany
    {
        return $this->hasMany(JobAssignment::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function commercialQuote(): HasOne
    {
        return $this->hasOne(Quote::class)->latestOfMany();
    }

    public function jobInspections(): HasMany
    {
        return $this->hasMany(JobInspection::class);
    }

    public function jobProgresses(): HasMany
    {
        return $this->hasMany(JobProgress::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Booking deposit amount that has actually been collected (0 when unpaid).
     */
    public function depositPaidAmount(): float
    {
        return $this->deposit_paid_at ? (float) $this->deposit_amount : 0.0;
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Determine whether technician assignment is unlocked based on scheduled date.
     */
    public function isTechnicianAssignmentAllowed(): bool
    {
        if (! $this->preferred_date) {
            return true;
        }

        // preferred_date is the customer's local calendar date, so compare with "today" in their zone.
        return $this->preferred_date->toDateString() <= BusinessTime::today($this->localTimezone());
    }

    /**
     * Get human-readable assignment unlock label or remaining days.
     */
    public function getAssignmentUnlockLabel(): ?string
    {
        if ($this->isTechnicianAssignmentAllowed() || ! $this->preferred_date) {
            return null;
        }

        $today = Carbon::parse(BusinessTime::today($this->localTimezone()));
        $target = Carbon::parse($this->preferred_date->toDateString());

        $days = (int) $today->diffInDays($target, false);

        if ($days <= 0) {
            return null;
        }

        return $days === 1
            ? "Unlocks tomorrow ({$this->preferred_date->format('M d, Y')})"
            : "Unlocks in {$days} days ({$this->preferred_date->format('M d, Y')})";
    }

    /**
     * The customer's timezone for this request (falls back to the caller's zone, then the business display zone).
     */
    public function localTimezone(): string
    {
        return BusinessTime::timezone($this->timezone);
    }

    /**
     * Hours before the scheduled start after which the customer can no longer reschedule.
     */
    public const RESCHEDULE_CUTOFF_HOURS = 24;

    /**
     * Scheduled start as a moment in time: the customer's wall-clock date and start time
     * in the booking's zone. Without a start time the day's start is used, so the
     * reschedule cutoff errs on the early side.
     */
    public function scheduledStartAt(): ?Carbon
    {
        if (! $this->preferred_date) {
            return null;
        }

        $time = $this->preferred_start_time
            ? Carbon::parse($this->preferred_start_time)->format('H:i:s')
            : '00:00:00';

        return Carbon::parse($this->preferred_date->toDateString().' '.$time, $this->localTimezone());
    }

    /**
     * Human-readable schedule in the customer's wall-clock time, e.g. "Mon, Nov 30, 2026 at 10:00 PM".
     */
    public function scheduleLabel(): string
    {
        if (! $this->preferred_date) {
            return 'As soon as possible';
        }

        $label = $this->preferred_date->format('D, M d, Y');

        if ($this->preferred_start_time) {
            $label .= ' at '.Carbon::parse($this->preferred_start_time)->format('g:i A');
        }

        if ($this->preferred_end_time) {
            $label .= ' – '.Carbon::parse($this->preferred_end_time)->format('g:i A');
        }

        return $label;
    }

    /**
     * Last moment the customer may change the date or time (24 hours before the scheduled start).
     */
    public function rescheduleDeadline(): ?Carbon
    {
        return $this->scheduledStartAt()?->copy()->subHours(self::RESCHEDULE_CUTOFF_HOURS);
    }

    /**
     * Who is told when the customer reschedules: the provider on the active job assignment
     * (or the one chosen for a direct request) and the assigned technician, if any.
     *
     * @return array{provider: ?User, technician: ?User}
     */
    public function rescheduleRecipients(): array
    {
        $assignment = $this->jobAssignments()
            ->where('status', '!=', JobAssignmentStatus::Declined->value)
            ->latest()
            ->first();

        return [
            'provider' => $assignment?->provider ?? $this->targetProvider,
            'technician' => $assignment?->technician,
        ];
    }

    /**
     * Whether the customer may still move this booking: it is not underway or closed,
     * and the cutoff has not passed.
     */
    public function canBeRescheduled(): bool
    {
        if (in_array($this->status, [ServiceRequestStatus::InProgress, ServiceRequestStatus::Completed, ServiceRequestStatus::Cancelled], true)) {
            return false;
        }

        $deadline = $this->rescheduleDeadline();

        return $deadline !== null && now()->lt($deadline);
    }

    /**
     * Resolve the active assigned provider user ID for this service request.
     */
    public function resolveProviderId(): ?int
    {
        if ($this->target_provider_id) {
            return (int) $this->target_provider_id;
        }

        $assignment = $this->jobAssignments()->latest()->first();
        if ($assignment && $assignment->provider_id) {
            return (int) $assignment->provider_id;
        }

        $quote = $this->quotes()->latest()->first();
        if ($quote && $quote->provider_id) {
            return (int) $quote->provider_id;
        }

        $jobReq = $this->providerJobRequests()
            ->whereIn('status', ['accepted', 'selected'])
            ->latest()
            ->first();
        if ($jobReq && $jobReq->provider_id) {
            return (int) $jobReq->provider_id;
        }

        $anyJobReq = $this->providerJobRequests()->latest()->first();
        if ($anyJobReq && $anyJobReq->provider_id) {
            return (int) $anyJobReq->provider_id;
        }

        return null;
    }
}
