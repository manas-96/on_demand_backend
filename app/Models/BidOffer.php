<?php

namespace App\Models;

use App\Enums\BidOfferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_bid_id',
        'sender_type',
        'sender_id',
        'amount',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => BidOfferStatus::class,
        ];
    }

    public function providerBid(): BelongsTo
    {
        return $this->belongsTo(ProviderBid::class, 'provider_bid_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
