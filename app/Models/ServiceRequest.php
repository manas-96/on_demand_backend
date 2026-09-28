<?php

namespace App\Models;

use App\Enums\RequestType;
use App\Enums\ServiceRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'preferred_date' => 'date',
            'request_type' => RequestType::class,
            'status' => ServiceRequestStatus::class,
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

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
