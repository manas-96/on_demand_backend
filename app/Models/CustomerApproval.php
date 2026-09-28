<?php

namespace App\Models;

use App\Enums\ApprovalType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'service_request_id',
        'quote_id',
        'approval_type',
        'status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approval_type' => ApprovalType::class,
            'approved_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
