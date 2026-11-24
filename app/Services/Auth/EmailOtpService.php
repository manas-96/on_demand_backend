<?php

namespace App\Services\Auth;

use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Issues and checks 6-digit email verification codes (same rules as the mobile API: 10 min, 5 attempts, 60 s resend).
 */
class EmailOtpService
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const VERIFIED = 'verified';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public function issue(User $user): void
    {
        $otp = (string) random_int(100000, 999999);

        EmailOtp::forUser($user)->delete();

        EmailOtp::create([
            'email' => $user->email,
            'role' => $user->role->value,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        if (app()->environment('local')) {
            Log::info("Verification OTP for {$user->email}: {$otp}");
        }

        try {
            $user->notify(new VerifyEmailOtpNotification($otp));
        } catch (Throwable $e) {
            Log::error("Failed to send OTP email to {$user->email}: {$e->getMessage()}");
        }
    }

    /**
     * Seconds the user must still wait before another code can be sent (0 when allowed).
     */
    public function secondsUntilResend(User $user): int
    {
        $existing = EmailOtp::forUser($user)->first();

        if (! $existing?->created_at) {
            return 0;
        }

        return max(0, self::RESEND_COOLDOWN_SECONDS - (int) $existing->created_at->diffInSeconds(now()));
    }

    /**
     * Check a code and mark the email verified on success. Returns one of the status constants.
     */
    public function verify(User $user, string $code): string
    {
        $record = EmailOtp::forUser($user)->first();

        if (! $record || now()->isAfter($record->expires_at)) {
            $record?->delete();

            return self::EXPIRED;
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->delete();

            return self::TOO_MANY_ATTEMPTS;
        }

        if (! hash_equals($record->otp, trim($code))) {
            $record->increment('attempts');

            return self::INVALID;
        }

        $record->delete();
        $user->markEmailAsVerified();

        return self::VERIFIED;
    }
}
