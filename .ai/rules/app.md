---
paths:
  - 'app/**'
---

# App

## Store and compute in UTC; local time only via BusinessTime
APP_TIMEZONE and the MySQL session are UTC. Never hardcode a zone (no now('Asia/Kolkata')). Use App\Services\BusinessTime for any "today/this week" logic: BusinessTime::today($zone), and BusinessTime::utcRangeForLocalDates() before querying timestamp columns with a local date range (whereDate on *_at columns is wrong). Zone precedence: booking's `timezone` column → `X-Timezone` request header (sent by both apps) → config app.display_timezone. preferred_date / preferred_*_time are customer wall-clock values: never convert them. In Blade/emails/PDFs display timestamps with ->inDisplayTimezone()->format(...).

## One account per (email, role); look users up with their role
users are unique on (email, role) and (phone, role): one person can have a customer and a provider account with the same email. Never look a user up by email alone — use User::findForAuth($login, $role) (falls back to the customer account when no role is sent), and scope OTP rows with EmailOtp::forUser($user). Uniqueness validation must be Rule::unique('users', 'email'|'phone')->where('role', ...). Self-dealing is forbidden: ProviderMatchingService excludes provider companies whose owner email equals the customer's, and direct requests to them fail validation.
