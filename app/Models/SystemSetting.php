<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_fee',
        'tax_rate',
        'minimum_booking_charge',
    ];

    protected function casts(): array
    {
        return [
            'platform_fee' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'minimum_booking_charge' => 'decimal:2',
        ];
    }

    /**
     * Retrieve the singleton settings record (creates default if none exists).
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'platform_fee' => 10.00,
                'tax_rate' => 5.00,
                'minimum_booking_charge' => 25.00,
            ]
        );
    }
}
