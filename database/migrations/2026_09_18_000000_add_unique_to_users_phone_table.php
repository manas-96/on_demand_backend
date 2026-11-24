<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Deduplicate any non-unique phones before adding unique constraint
        $duplicates = DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        foreach ($duplicates as $dupPhone) {
            $users = DB::table('users')->where('phone', $dupPhone)->orderBy('id')->get();
            foreach ($users->slice(1) as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'phone' => $dupPhone.'-dup-'.$user->id,
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
