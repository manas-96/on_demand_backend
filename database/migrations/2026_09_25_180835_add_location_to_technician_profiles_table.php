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
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->decimal('current_latitude', 10, 7)->nullable()->after('duty_status');
            $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            $table->timestamp('last_location_at')->nullable()->after('current_longitude');

            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technician_profiles', function (Blueprint $table) {
            $table->dropIndex(['current_latitude', 'current_longitude']);
            $table->dropColumn(['current_latitude', 'current_longitude', 'last_location_at']);
        });
    }
};
