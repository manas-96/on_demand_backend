<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user is an Administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Check if user is a Customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Check if user is a Service Provider.
     */
    /**
     * Resolve the account a login / OTP / password-reset request refers to.
     *
     * One email (or phone) may have one account per role. Clients should send the role; older customer-app
     * builds don't, so when the role is missing and several accounts match, the customer account is used.
     */
    public static function findForAuth(string $login, ?string $role = null): ?self
    {
        $matches = static::query()
            ->where(fn ($query) => $query->where('email', $login)->orWhere('phone', $login))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->get();

        if ($matches->count() <= 1) {
            return $matches->first();
        }

        return $matches->first(fn (self $user) => $user->role === UserRole::Customer);
    }

    public function isProvider(): bool
    {
        return $this->role === UserRole::Provider;
    }

    /**
     * Check if user is a Technician.
     */
    public function isTechnician(): bool
    {
        return $this->role === UserRole::Technician;
    }

    /**
     * Get the provider profile if user is a provider.
     */
    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    /**
     * Get the technician profile if user is a technician.
     */
    public function technicianProfile(): HasOne
    {
        return $this->hasOne(TechnicianProfile::class);
    }

    /**
     * Get technicians employed by this user (if user is a provider).
     */
    public function employedTechnicians(): HasMany
    {
        return $this->hasMany(TechnicianProfile::class, 'provider_id');
    }

    /**
     * Get customer addresses.
     */
    public function customerAddresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get user FCM device tokens.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Get in-app notifications.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
