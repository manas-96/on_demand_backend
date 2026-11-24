<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'icon',
        'image',
        'minimum_booking_charge',
        'tags',
        'is_active',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'minimum_booking_charge' => 'decimal:2',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        return asset('storage/'.$this->image);
    }

    public function getEffectiveMinimumBookingChargeAttribute(): float
    {
        if ((float) $this->minimum_booking_charge > 0) {
            return (float) $this->minimum_booking_charge;
        }

        if ($this->parent_id) {
            $parent = $this->parent ?? ServiceCategory::find($this->parent_id);
            if ($parent && (float) $parent->minimum_booking_charge > 0) {
                return (float) $parent->minimum_booking_charge;
            }
        }

        $lower = strtolower($this->name);
        if (str_contains($lower, 'ac') || str_contains($lower, 'refrigeration')) {
            return 499.00;
        }
        if (str_contains($lower, 'electric')) {
            return 199.00;
        }
        if (str_contains($lower, 'clean')) {
            return 350.00;
        }
        if (str_contains($lower, 'paint')) {
            return 500.00;
        }
        if (str_contains($lower, 'pest')) {
            return 399.00;
        }
        if (str_contains($lower, 'appliance')) {
            return 299.00;
        }

        return 250.00;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'parent_id');
    }

    public function subcategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class, 'parent_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'service_category_id');
    }

    public function scopeParents(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOnlySubcategories(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }
}
