<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use App\Notifications\VerifyEmailOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new customer user and send email OTP.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50', 'unique:users,phone'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'role' => ['nullable', 'string', 'in:customer,provider,technician'],
        ]);

        $roleValue = $validated['role'] ?? UserRole::Customer->value;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => UserRole::from($roleValue),
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $otp = (string) random_int(100000, 999999);

        EmailOtp::where('email', $user->email)->delete();

        EmailOtp::create([
            'email' => $user->email,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (app()->environment('local')) {
            Log::info("Verification OTP for {$user->email}: {$otp}");
        }

        try {
            $user->notify(new VerifyEmailOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error("Failed to send OTP email to {$user->email}: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. A verification code has been sent to your email address.',
            'data' => [
                'email' => $user->email,
                'requires_verification' => true,
            ],
        ], 201);
    }

    /**
     * Verify email with 6-digit OTP and issue Sanctum token.
     */
    public function verifyEmailOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address.',
            ], 404);
        }

        $otpRecord = EmailOtp::where('email', $validated['email'])->first();

        if (! $otpRecord || now()->isAfter($otpRecord->expires_at)) {
            if ($otpRecord) {
                $otpRecord->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.',
            ], 422);
        }

        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();

            return response()->json([
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.',
            ], 429);
        }

        if ($otpRecord->otp !== $validated['otp']) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please check and try again.',
            ], 422);
        }

        $otpRecord->delete();
        $user->markEmailAsVerified();

        $token = $user->createToken('mobile_device_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Resend a fresh 6-digit email OTP.
     */
    public function resendEmailOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found with this email address.',
            ], 404);
        }

        $existing = EmailOtp::where('email', $user->email)->first();

        if ($existing && $existing->created_at && $existing->created_at->diffInSeconds(now()) < 60) {
            $secondsRemaining = 60 - (int) $existing->created_at->diffInSeconds(now());

            return response()->json([
                'success' => false,
                'message' => 'Please wait '.$secondsRemaining.' seconds before requesting another code.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        EmailOtp::where('email', $user->email)->delete();

        EmailOtp::create([
            'email' => $user->email,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (app()->environment('local')) {
            Log::info("Resent verification OTP for {$user->email}: {$otp}");
        }

        try {
            $user->notify(new VerifyEmailOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error("Failed to resend OTP email to {$user->email}: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'message' => 'A new verification code has been sent to your email address.',
        ]);
    }

    /**
     * Authenticate user and issue Sanctum token (or dispatch login OTP for customer accounts).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginInput = $request->input('login') ?? $request->input('email') ?? $request->input('phone');

        if (! $loginInput) {
            return response()->json([
                'success' => false,
                'message' => 'Email or phone number is required.',
            ], 422);
        }

        $user = User::where('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($request->filled('role') && $user->role->value !== $request->input('role')) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Account is not registered as a '.$request->input('role').'.',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is deactivated.',
            ], 403);
        }

        // For Customer accounts, require 6-digit OTP verification on every login
        if ($user->role === UserRole::Customer) {
            $otp = (string) random_int(100000, 999999);

            EmailOtp::where('email', $user->email)->delete();

            EmailOtp::create([
                'email' => $user->email,
                'otp' => $otp,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]);

            if (app()->environment('local')) {
                Log::info("Login verification OTP for {$user->email}: {$otp}");
            }

            try {
                $user->notify(new VerifyEmailOtpNotification($otp));
            } catch (\Throwable $e) {
                Log::error("Failed to send login OTP email to {$user->email}: {$e->getMessage()}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Credentials verified. A verification code has been sent to your email address.',
                'data' => [
                    'requires_otp' => true,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
        }

        $token = $user->createToken('mobile_device_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Return authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'User profile retrieved.',
            'data' => new UserResource($request->user()),
        ]);
    }

    /**
     * Revoke current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Send a 6-digit password reset OTP to user's email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is deactivated. Please contact support.',
            ], 403);
        }

        $existing = EmailOtp::where('email', $user->email)->first();

        if ($existing && $existing->created_at && $existing->created_at->diffInSeconds(now()) < 60) {
            $secondsRemaining = 60 - (int) $existing->created_at->diffInSeconds(now());

            return response()->json([
                'success' => false,
                'message' => 'Please wait '.$secondsRemaining.' seconds before requesting another code.',
            ], 429);
        }

        $otp = (string) random_int(100000, 999999);

        EmailOtp::where('email', $user->email)->delete();

        EmailOtp::create([
            'email' => $user->email,
            'otp' => $otp,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        if (app()->environment('local')) {
            Log::info("Password reset OTP for {$user->email}: {$otp}");
        }

        try {
            $user->notify(new ResetPasswordOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error("Failed to send password reset OTP email to {$user->email}: {$e->getMessage()}");
        }

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit password reset code has been sent to your email address.',
            'data' => [
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Verify password reset OTP and update user's password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this email address.',
            ], 404);
        }

        $otpRecord = EmailOtp::where('email', $validated['email'])->first();

        if (! $otpRecord || now()->isAfter($otpRecord->expires_at)) {
            if ($otpRecord) {
                $otpRecord->delete();
            }

            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.',
            ], 422);
        }

        if ($otpRecord->attempts >= 5) {
            $otpRecord->delete();

            return response()->json([
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.',
            ], 429);
        }

        if ($otpRecord->otp !== $validated['otp']) {
            $otpRecord->increment('attempts');

            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code. Please check and try again.',
            ], 422);
        }

        // OTP is valid - delete it and update password
        $otpRecord->delete();
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Invalidate existing sessions/tokens for security
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now log in with your new password.',
        ]);
    }
}
