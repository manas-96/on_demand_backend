<?php

namespace App\Services;

use Carbon\CarbonInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;

/**
 * Single place for "local time" decisions.
 *
 * Everything is stored and computed in UTC (config app.timezone). Local time is only used to decide
 * what "today" / "this week" means for someone and to display timestamps. The local zone is, in order:
 *  1. an explicit zone (e.g. the zone saved on a service request),
 *  2. the caller's `X-Timezone` header (sent by the mobile apps),
 *  3. config app.display_timezone (web panels, emails, PDFs).
 */
class BusinessTime
{
    public const HEADER = 'X-Timezone';

    public static function displayTimezone(): string
    {
        return (string) config('app.display_timezone', 'UTC');
    }

    /**
     * Resolve the local zone to use, ignoring anything that isn't a valid IANA identifier.
     */
    public static function timezone(?string $preferred = null): string
    {
        foreach ([$preferred, self::requestTimezone()] as $candidate) {
            if (self::isValid($candidate)) {
                return $candidate;
            }
        }

        return self::displayTimezone();
    }

    public static function requestTimezone(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $header = request()->header(self::HEADER);

        return self::isValid($header) ? $header : null;
    }

    public static function isValid(?string $timezone): bool
    {
        return is_string($timezone) && $timezone !== '' && in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true);
    }

    /**
     * Current moment expressed in the local zone.
     */
    public static function now(?string $timezone = null): Carbon
    {
        return Carbon::now(self::timezone($timezone));
    }

    /**
     * Today's local date as Y-m-d.
     */
    public static function today(?string $timezone = null): string
    {
        return self::now($timezone)->toDateString();
    }

    /**
     * UTC instants covering the local calendar days [$fromDate, $toDate] (inclusive), for querying timestamp columns.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function utcRangeForLocalDates(CarbonInterface|string $fromDate, CarbonInterface|string $toDate, ?string $timezone = null): array
    {
        $zone = self::timezone($timezone);

        $start = Carbon::parse(self::dateString($fromDate), $zone)->startOfDay()->utc();
        $end = Carbon::parse(self::dateString($toDate), $zone)->endOfDay()->utc();

        return [$start, $end];
    }

    /**
     * Convert a stored (UTC) timestamp for display in the local zone.
     */
    public static function toLocal(?CarbonInterface $moment, ?string $timezone = null): ?Carbon
    {
        return $moment ? Carbon::instance($moment)->setTimezone(self::timezone($timezone)) : null;
    }

    private static function dateString(CarbonInterface|string $date): string
    {
        return $date instanceof CarbonInterface ? $date->toDateString() : substr(trim($date), 0, 10);
    }
}
