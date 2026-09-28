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
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('is_approved_by_provider')->default(false)->after('status');
            $table->timestamp('provider_approved_at')->nullable()->after('is_approved_by_provider');

            $table->index(['service_request_id', 'is_approved_by_provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['service_request_id', 'is_approved_by_provider']);
            $table->dropColumn(['is_approved_by_provider', 'provider_approved_at']);
        });
    }
};
