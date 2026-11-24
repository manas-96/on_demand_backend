<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two-phase customer payments: an upfront booking deposit and the remaining balance after completion.
     */
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->default(0.00)->after('priority');
            $table->timestamp('deposit_paid_at')->nullable()->after('deposit_amount');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->change();
            $table->foreignId('service_request_id')->nullable()->after('invoice_id')->constrained('service_requests')->cascadeOnDelete();
            $table->string('type')->default('balance')->after('service_request_id');
            $table->string('currency', 3)->nullable()->after('amount');

            $table->index(['service_request_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['service_request_id', 'type', 'status']);
            $table->dropConstrainedForeignId('service_request_id');
            $table->dropColumn(['type', 'currency']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'deposit_paid_at']);
        });
    }
};
