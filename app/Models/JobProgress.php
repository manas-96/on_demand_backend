<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobProgress extends Model
{
    use HasFactory;

    protected $table = 'job_progress';

    protected $fillable = [
        'service_request_id',
        'technician_id',
        'progress_percentage',
        'description',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'integer',
            'photos' => 'array',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
