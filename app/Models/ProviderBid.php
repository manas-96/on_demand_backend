<?php

namespace App\Models;

use App\Enums\ProviderBidStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderBid extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_request_id',
        'provider_id',
        'amount',
        'estimated_duration',
        'availability_date',
        'message',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'availability_date' => 'date',
            'status' => ProviderBidStatus::class,
            'expires_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(BidOffer::class, 'provider_bid_id');
    }
}
