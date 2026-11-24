<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One person may hold separate accounts per role (e.g. a customer account and a provider account) with the
 * same email/phone. Uniqueness becomes (email, role) and (phone, role), and email OTP codes belong to a role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['phone']);
            $table->unique(['email', 'role']);
            $table->unique(['phone', 'role']);
        });

        // Codes live 10 minutes; clear any in flight so every remaining code carries a role.
        DB::table('email_otps')->delete();

        Schema::table('email_otps', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->after('email');
            $table->index(['email', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('email_otps', function (Blueprint $table) {
            $table->dropIndex(['email', 'role']);
            $table->dropColumn('role');
        });

        // Fails if an email/phone is now shared by several accounts; merge or remove those first.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email', 'role']);
            $table->dropUnique(['phone', 'role']);
            $table->unique('email');
            $table->unique('phone');
        });
    }
};
