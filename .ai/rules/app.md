---
paths:
  - 'app/**'
---

# App

## Store and compute in UTC; local time only via BusinessTime
APP_TIMEZONE and the MySQL session are UTC. Never hardcode a zone (no now('Asia/Kolkata')). Use App\Services\BusinessTime for any "today/this week" logic: BusinessTime::today($zone), and BusinessTime::utcRangeForLocalDates() before querying timestamp columns with a local date range (whereDate on *_at columns is wrong). Zone precedence: booking's `timezone` column → `X-Timezone` request header (sent by both apps) → config app.display_timezone. preferred_date / preferred_*_time are customer wall-clock values: never convert them. In Blade/emails/PDFs display timestamps with ->inDisplayTimezone()->format(...).
