<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_request_id',
        'provider_id',
        'technician_id',
        'labor_subtotal',
        'material_subtotal',
        'tax',
        'discount',
        'total',
        'notes',
        'status',
        'is_approved_by_provider',
        'provider_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'labor_subtotal' => 'decimal:2',
            'material_subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => QuoteStatus::class,
            'is_approved_by_provider' => 'boolean',
            'provider_approved_at' => 'datetime',
        ];
    }

    public function scopeApprovedByProvider($query)
    {
        return $query->where('is_approved_by_provider', true);
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

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }
}
