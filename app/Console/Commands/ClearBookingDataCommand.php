<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearBookingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all booking and service request related data from the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting database cleanup for booking-related tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'job_progress',
            'job_inspections',
            'job_status_histories',
            'customer_approvals',
            'quote_items',
            'quotes',
            'job_assignments',
            'provider_bids',
            'bid_offers',
            'provider_job_requests',
            'request_photos',
            'reviews',
            'tips',
            'payments',
            'invoice_items',
            'invoices',
            'provider_earnings',
            'service_requests',
        ];

        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                DB::table($t)->truncate();
                $this->line(" - Truncated table: <comment>{$t}</comment>");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('All booking-related data cleared successfully!');

        return Command::SUCCESS;
    }
}
