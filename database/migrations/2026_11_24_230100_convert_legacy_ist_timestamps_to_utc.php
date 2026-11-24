<?php

use Carbon\CarbonTimeZone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One-off correction when moving from APP_TIMEZONE=Asia/Kolkata to UTC.
 *
 * Laravel used to write IST wall-clock values. What that means for stored data depends on the server:
 *  - TIMESTAMP columns are converted by MySQL using the *session* timezone. If MySQL ran in IST, the stored
 *    instants are already correct and nothing changes. If MySQL ran in UTC, they are 5h30m ahead and are shifted.
 *  - DATETIME columns store the wall-clock value as-is, so IST values are always shifted to UTC.
 * DATE / TIME columns (preferred_date, preferred_*_time) are customer wall-clock values and are never touched.
 *
 * The legacy MySQL offset is measured from @@global.time_zone; override it with
 * TZ_MIGRATION_LEGACY_DB_OFFSET_MINUTES if the server's global zone changed since the data was written.
 */
return new class extends Migration
{
    private const LEGACY_APP_TIMEZONE = 'Asia/Kolkata';

    public function up(): void
    {
        $this->shift(direction: -1);
    }

    public function down(): void
    {
        $this->shift(direction: 1);
    }

    private function shift(int $direction): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (config('app.timezone') !== 'UTC') {
            throw new RuntimeException('Set APP_TIMEZONE=UTC (and DB_TIMEZONE=+00:00) before running this migration.');
        }

        $legacyAppOffset = intdiv(CarbonTimeZone::create(self::LEGACY_APP_TIMEZONE)->getOffset(now()), 60);
        $legacyDbOffset = $this->legacyDatabaseOffsetMinutes();

        $plan = [
            'timestamp' => $legacyAppOffset - $legacyDbOffset,
            'datetime' => $legacyAppOffset,
        ];

        $columns = DB::select(
            'select table_name as tbl, column_name as col, data_type as type from information_schema.columns
             where table_schema = database() and data_type in (?, ?)',
            ['timestamp', 'datetime']
        );

        $updated = 0;

        DB::transaction(function () use ($columns, $plan, $direction, &$updated) {
            foreach ($columns as $column) {
                $minutes = $plan[strtolower($column->type)] ?? 0;

                if ($minutes === 0) {
                    continue;
                }

                $operator = $direction < 0 ? '-' : '+';
                $updated += DB::update(sprintf(
                    'update `%s` set `%s` = `%s` %s interval %d minute where `%s` is not null',
                    $column->tbl, $column->col, $column->col, $operator, $minutes, $column->col
                ));
            }
        });

        Log::info('UTC timestamp migration', [
            'direction' => $direction < 0 ? 'up' : 'down',
            'legacy_db_offset_minutes' => $legacyDbOffset,
            'shift_minutes' => $plan,
            'rows_updated' => $updated,
        ]);
    }

    /**
     * Offset (minutes) of the MySQL session zone the old code wrote with, i.e. the server's global zone.
     */
    private function legacyDatabaseOffsetMinutes(): int
    {
        $override = env('TZ_MIGRATION_LEGACY_DB_OFFSET_MINUTES');

        if ($override !== null && $override !== '') {
            return (int) $override;
        }

        DB::statement('set time_zone = @@global.time_zone');
        $seconds = (int) DB::selectOne('select timestampdiff(second, utc_timestamp(), now()) as diff')->diff;
        DB::statement("set time_zone = '+00:00'");

        return (int) round($seconds / 60);
    }
};
