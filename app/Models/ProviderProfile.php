<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderProfile extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'business_registration',
        'address',
        'service_radius_km',
        'commission',
        'latitude',
        'longitude',
        'is_approved',
        'is_accepting_jobs',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_radius_km' => 'decimal:2',
            'commission' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_approved' => 'boolean',
            'is_accepting_jobs' => 'boolean',
        ];
    }

    /**
     * Get the user account associated with the provider profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get technicians belonging to this provider profile.
     */
    public function technicians(): HasMany
    {
        return $this->hasMany(TechnicianProfile::class, 'provider_profile_id');
    }

    /**
     * Get provider service offerings.
     */
    public function providerServices(): HasMany
    {
        return $this->hasMany(ProviderService::class, 'provider_profile_id');
    }

    /**
     * Get services offered by this provider profile.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'provider_services', 'provider_profile_id', 'service_id')
            ->withPivot(['custom_price', 'is_offered'])
            ->withTimestamps();
    }
}
