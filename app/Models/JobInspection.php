<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobInspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'job_assignment_id',
        'technician_id',
        'technical_notes',
        'estimated_labor_hours',
        'estimated_material_cost',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'estimated_labor_hours' => 'decimal:2',
            'estimated_material_cost' => 'decimal:2',
            'photos' => 'array',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function jobAssignment(): BelongsTo
    {
        return $this->belongsTo(JobAssignment::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
