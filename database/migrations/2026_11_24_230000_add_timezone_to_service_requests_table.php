<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer's IANA timezone (e.g. America/Chicago). preferred_date / preferred_*_time are wall-clock
     * values in this zone; it also decides what "today" means for the request's business rules.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('preferred_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
