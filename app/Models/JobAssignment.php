<?php

namespace App\Models;

use App\Enums\JobAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'provider_id',
        'technician_id',
        'assigned_at',
        'accepted_at',
        'arrived_at',
        'started_at',
        'completed_at',
        'status',
    ];

    protected $appends = [
        'can_chat_customer',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getCanChatCustomerAttribute(): bool
    {
        return $this->technician_id !== null && $this->status !== JobAssignmentStatus::Declined;
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function jobInspections(): HasMany
    {
        return $this->hasMany(JobInspection::class);
    }

    public function jobProgresses(): HasMany
    {
        return $this->hasMany(JobProgress::class, 'service_request_id', 'service_request_id');
    }
}
