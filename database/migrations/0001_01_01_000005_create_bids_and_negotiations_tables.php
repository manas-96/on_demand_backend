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
        Schema::create('provider_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained('service_requests')->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('estimated_duration')->nullable();
            $table->date('availability_date')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['service_request_id', 'status']);
            $table->index(['provider_id', 'status']);
        });

        Schema::create('bid_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_bid_id')->constrained('provider_bids')->cascadeOnDelete();
            $table->string('sender_type'); // 'customer' or 'provider'
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['provider_bid_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bid_offers');
        Schema::dropIfExists('provider_bids');
    }
};
