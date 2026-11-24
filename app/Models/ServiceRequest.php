<?php

namespace App\Models;

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
