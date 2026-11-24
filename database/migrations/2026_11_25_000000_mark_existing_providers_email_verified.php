<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Providers created before email verification existed (by admins or the old self-registration) are treated as
 * verified, so the new onboarding gate does not lock them out. Approval status is left unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'provider')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Not reversible: we cannot tell which providers were verified by this migration.
    }
};
