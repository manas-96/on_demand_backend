# AI_AGENT_RULES.md
# Laravel Backend — Service Request, Provider Bidding & Technician Job Platform

## 1. Purpose

This document is the **strict backend development contract for AI coding agents**.

The backend powers:

1. Customer Mobile App
2. Service Provider Web Panel
3. Service Technician Mobile App
4. Admin Web Panel
5. REST APIs used by Customer and Technician mobile applications

The backend MUST implement the business workflow defined by the project, not merely CRUD screens.

The most important architectural distinction is:

```text
CUSTOMER
   |
   | creates service request
   v
SERVICE REQUEST
   |
   +------------------------------+
   |                              |
   v                              v
NEARBY PROVIDERS              SINGLE PROVIDER
   |                              |
   | providers bid                | direct request
   +---------------+--------------+
                   |
                   v
             NEGOTIATION
                   |
                   v
          CUSTOMER SELECTS BID
                   |
                   v
          SERVICE PROVIDER
                   |
                   | assigns technician
                   v
             TECHNICIAN
                   |
                   v
          INSPECTION / QUOTE
                   |
                   v
         CUSTOMER APPROVAL
                   |
                   v
             JOB EXECUTION
                   |
                   v
       ADDITIONAL CHARGES
                   |
                   v
          FINAL INVOICE
                   |
                   v
               PAYMENT
                   |
                   v
             COMPLETION
                   |
                   v
          TIP / REVIEW
```

---

# 2. Non-Negotiable Rules

AI agents MUST follow these rules.

### Rule 1 — Backend is the source of truth

Never trust the mobile app or web panel for:

- price calculations
- totals
- tax
- status changes
- permissions
- provider eligibility
- technician assignment
- payment status
- quote approval
- bid selection
- job completion

All critical business rules MUST be validated server-side.

### Rule 2 — Never bypass the workflow

Do not create APIs that allow users to jump directly between unrelated states.

Example:

```text
request_submitted -> completed
```

is invalid.

### Rule 3 — Customer, Provider and Technician are different actors

Do NOT treat a technician as an independent service provider.

A technician belongs to a service provider organization.

```text
Provider Company
    ├── Technician A
    ├── Technician B
    └── Technician C
```

The provider bids for the job.

The provider assigns one of its technicians to execute the accepted job.

### Rule 4 — Admin is never a normal customer/provider/technician

Admin authorization must be separate.

### Rule 5 — Every important business action must be auditable

Record:

- who performed the action
- what changed
- previous value
- new value
- timestamp
- related request/job
- optional reason

### Rule 6 — Never hard-delete financial records

Invoices, payments, earnings, tips and transaction records must use safe archival/void/refund mechanisms.

### Rule 7 — Database transactions are mandatory for multi-step financial/workflow operations.

---

# 3. Technology

Use:

```text
PHP
Laravel
MySQL
Laravel Sanctum
Laravel Queues
Laravel Events / Listeners
Laravel Notifications
Laravel Storage
REST JSON APIs
```

Recommended supporting components:

```text
Redis       -> cache/queue when available
Firebase    -> push notifications
MySQL       -> primary database
Laravel Scheduler -> expiry/background tasks
```

Do not introduce another backend framework unless explicitly requested.

---

# 4. Backend Architecture

Use:

```text
HTTP Request
    ↓
Route
    ↓
Middleware
    ↓
Form Request
    ↓
Controller
    ↓
Application Service
    ↓
Domain/Business Rules
    ↓
Repository / Eloquent
    ↓
Database
```

Recommended structure:

```text
app/
├── Enums/
├── Events/
├── Exceptions/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   ├── Provider/
│   │   └── Api/
│   ├── Middleware/
│   ├── Requests/
│   └── Resources/
├── Jobs/
├── Listeners/
├── Models/
├── Notifications/
├── Policies/
├── Repositories/
├── Rules/
├── Services/
│   ├── Request/
│   ├── Matching/
│   ├── Bidding/
│   ├── Negotiation/
│   ├── Assignment/
│   ├── Quote/
│   ├── Job/
│   ├── Invoice/
│   ├── Payment/
│   └── Notification/
└── Support/
```

Controllers MUST remain thin.

Do not put business logic inside:

- Blade files
- API controllers
- Eloquent accessors containing workflow logic
- mobile-app assumptions

---

# 5. Actor / Role Model

Required roles:

```text
admin
customer
provider
technician
```

Hierarchy:

```text
Admin
 ├── manages customers
 ├── manages providers
 ├── manages technicians
 └── manages platform

Provider
 ├── company/profile
 ├── services
 ├── technicians
 ├── bids
 ├── negotiations
 ├── jobs
 └── earnings

Technician
 ├── belongs to provider
 ├── receives assigned jobs
 ├── travels to job
 ├── inspects job
 ├── executes job
 └── updates job progress

Customer
 ├── creates requests
 ├── receives bids
 ├── negotiates
 ├── selects provider
 ├── approves quotes
 ├── approves extras
 ├── pays
 ├── tips
 └── reviews
```

A technician MUST always have a `provider_id`.

A technician cannot bid independently unless the business requirements are explicitly changed.

---

# 6. Provider vs Technician

This distinction is mandatory.

## Provider

Responsible for:

```text
Receiving service requests
Bidding
Negotiating
Managing technicians
Accepting awarded jobs
Submitting commercial quotes where applicable
Managing job execution
Managing invoices
Viewing earnings
```

## Technician

Responsible for:

```text
Receiving assigned job
Accepting/acknowledging assignment
Navigation
Marking arrival
Inspection
Inspection photos
Technical notes
Labor/material updates
Job progress
Additional service requests
Completion
```

The technician app must not expose provider-level financial/account administration.

---

# 7. Service Request Creation

Customer creates:

```text
Service Request
```

Required/optional data may include:

```text
service
description
photos
address
latitude
longitude
preferred date
preferred start time
preferred end time
priority
```

The server generates:

```text
request_number
```

Example:

```text
SR-20260903-000001
```

Database IDs are internal.

Public request numbers are customer-facing identifiers.

---

# 8. Request Types

Every request must have a request source/type.

Recommended:

```text
broadcast
direct
```

## Broadcast

Customer submits a request to the platform.

System finds eligible nearby providers.

```text
Customer
   ↓
Service Request
   ↓
Provider Matching
   ↓
Multiple Providers
   ↓
Provider Bids
```

## Direct

Customer selects a particular provider.

```text
Customer
   ↓
Selected Provider
   ↓
Direct Job Request
   ↓
Provider
   ↓
Bid / Quote / Response
```

A direct request MUST NOT be broadcast to unrelated providers.

---

# 9. Provider Matching

For broadcast requests, matching must happen server-side.

Provider eligibility may include:

```text
provider.active = true
provider.approved = true
provider offers requested service
provider is within configured service radius
provider is accepting jobs
provider has eligible technicians
```

Location matching should use:

```text
latitude
longitude
service_radius
```

Do not calculate geographic distance using inaccurate string comparisons.

Use a proper geographic-distance calculation.

The matching algorithm must be isolated in:

```text
ProviderMatchingService
```

Do not place matching logic inside the controller.

---

# 10. Provider Job Invitation

When a provider is matched, create a provider-specific request/invitation.

Example:

```text
provider_job_requests
```

Possible states:

```text
sent
viewed
bid_submitted
declined
expired
selected
rejected
```

The customer request and provider invitation are different concepts.

Do not duplicate the complete service request for every provider.

---

# 11. Provider Bidding

For broadcast requests, providers submit bids.

Recommended entity:

```text
provider_bids
```

Minimum conceptual fields:

```text
id
service_request_id
provider_id
amount
estimated_duration
availability_date
message
status
created_at
updated_at
expires_at
```

Bid statuses:

```text
draft
submitted
under_negotiation
accepted
rejected
withdrawn
expired
```

The backend MUST calculate/validate the bid amount according to business rules.

Do not allow a provider to modify an accepted bid.

---

# 12. One Provider Can Submit Multiple Bid Versions

Negotiation must not overwrite history.

Bad:

```text
bid.amount = 800
bid.amount = 700
```

This destroys audit history.

Preferred:

```text
bid
  ↓
negotiation
  ↓
offer 1 = ₹800
offer 2 = ₹750
offer 3 = ₹700
```

Every negotiation offer must remain auditable.

Recommended:

```text
bid_negotiations
```

or:

```text
bid_offers
```

with:

```text
bid_id
sender_type
sender_id
amount
message
status
created_at
```

---

# 13. Customer Negotiation

Customer can negotiate with a provider after receiving a bid.

Allowed actors:

```text
customer
provider
```

A negotiation belongs to:

```text
service_request
provider
bid
```

Never allow:

```text
customer A
```

to negotiate on:

```text
customer B's request
```

Never allow one provider to see another provider's private negotiation messages/offers.

---

# 14. Bid Selection

Customer may select one provider/bid.

Once selected:

```text
selected provider = authoritative provider
selected bid = authoritative commercial offer
```

All other active bids must become:

```text
rejected
```

or:

```text
closed
```

depending on business terminology.

This operation MUST be atomic.

Use a database transaction.

Prevent two providers from simultaneously becoming the selected provider.

Use proper database locking/constraints where necessary.

---

# 15. Direct Provider Request

Customer may send a request to exactly one provider.

Flow:

```text
Customer selects provider
        ↓
Create service request
        ↓
provider_job_request
        ↓
Provider receives request
        ↓
Provider responds
        ↓
Bid / quote
        ↓
Customer negotiates
        ↓
Customer accepts
```

Direct requests must be identifiable:

```text
request_type = direct
provider_id = selected provider
```

---

# 16. Provider Acceptance

Do not confuse:

```text
provider receiving request
```

with:

```text
customer selecting provider
```

For broadcast requests:

```text
Provider submits bid
        ↓
Customer selects bid
        ↓
Provider is awarded job
```

For direct requests:

```text
Provider receives request
        ↓
Provider submits bid/response
        ↓
Customer accepts
```

---

# 17. Technician Assignment

After a provider wins/accepts the job, the provider assigns a technician.

Flow:

```text
Customer
   ↓
Provider selected
   ↓
Provider assigns technician
   ↓
Technician receives assignment
   ↓
Technician accepts
   ↓
Technician travels
```

Recommended entity:

```text
job_assignments
```

It must identify:

```text
service_request_id
provider_id
technician_id
assigned_at
accepted_at
arrived_at
started_at
completed_at
status
```

A technician can only be assigned if:

```text
technician.provider_id == assigned_provider.id
```

This rule is mandatory.

---

# 18. Technician Availability

Provider must not assign an unavailable technician.

Validate:

```text
technician.active
technician.approved
technician.belongs to provider
technician is available
technician is not already assigned to an overlapping active job
```

The server must perform the final validation.

---

# 19. Technician Workflow

The technician workflow must follow:

```text
Assigned
   ↓
Accepted
   ↓
En Route
   ↓
Arrived
   ↓
Inspecting
   ↓
Quote/Scope Confirmation
   ↓
Customer Approval
   ↓
In Progress
   ↓
Additional Charges (if required)
   ↓
Completed
```

The technician app structure already separates job dispatch, navigation/tracking, inspection/quote, job execution and invoice/settlement responsibilities. fileciteturn1file1L34-L42

---

# 20. Status State Machine

Create centralized enums.

Example:

```text
ServiceRequestStatus
ProviderBidStatus
ProviderJobRequestStatus
JobAssignmentStatus
QuoteStatus
AdditionalServiceStatus
PaymentStatus
InvoiceStatus
```

Do NOT scatter status strings throughout the code.

Every status transition must pass through a service such as:

```text
ServiceRequestStateService
JobStateService
BidStateService
```

Example:

```php
$jobStateService->transition(
    $job,
    JobStatus::IN_PROGRESS
);
```

Invalid transitions must throw a domain/business exception.

---

# 21. Status History

Every important status change must create history.

Recommended:

```text
job_status_history
```

Store:

```text
service_request_id
job_assignment_id
changed_by_type
changed_by_id
old_status
new_status
reason
created_at
```

Do not depend only on the current status column.

---

# 22. Inspection

Technician performs the physical inspection.

Inspection can contain:

```text
notes
estimated_labor_hours
estimated_material_cost
inspection photos
technical findings
```

Inspection data must be linked to:

```text
service_request
job_assignment
technician
```

Never allow a technician from Provider B to modify Provider A's inspection.

---

# 23. Quote

After inspection, the provider/technician workflow can produce a quote depending on the commercial model.

The quote belongs to:

```text
service_request
provider
```

It may reference:

```text
technician
inspection
```

Quote contains:

```text
labor
materials
additional services
subtotal
tax
discount
total
notes
```

All financial totals must be recalculated server-side.

---

# 24. Quote Items

Use line items.

Types:

```text
labor
material
additional_service
```

Each item:

```text
description
quantity
unit_price
total_price
```

Calculation:

```text
total_price = quantity × unit_price
```

Never trust:

```text
total_price
subtotal
tax
grand_total
```

sent by the mobile app.

---

# 25. Customer Approval

Customer must explicitly approve the commercial quote.

Approval must record:

```text
customer_id
request_id
quote_id
approval_type
status
approved_at
```

Possible:

```text
pending
approved
rejected
```

Viewing a quote is NOT approval.

---

# 26. Job Start

A technician cannot start the job until required approvals are completed.

Minimum logical condition:

```text
provider awarded
AND technician assigned
AND technician accepted
AND inspection completed
AND quote approved
```

Then:

```text
job status = in_progress
```

The backend must validate this.

---

# 27. Job Progress

Technician can update:

```text
progress percentage
description
photos
technical notes
```

Progress percentage must be:

```text
0 <= progress <= 100
```

Do not allow invalid values.

---

# 28. Materials and Labor

Labor:

```text
hours
hourly_rate
total
```

Materials:

```text
name
quantity
unit_price
total
```

Server calculation:

```text
labor_total = hours × hourly_rate
material_total = quantity × unit_price
```

Never trust client totals.

---

# 29. Additional Services / Charges

If work scope changes:

```text
Technician
    ↓
Provider
    ↓
Additional Charge Request
    ↓
Customer
    ↓
Approve / Reject
```

Customer approval is mandatory before charging for the additional work.

Never silently add additional charges to the final invoice.

---

# 30. Final Invoice

Invoice must be generated from authoritative server records.

Invoice can contain:

```text
accepted bid
approved quote
labor
materials
approved additional services
tax
discount
tip
```

Recommended lifecycle:

```text
draft
issued
partially_paid
paid
cancelled
refunded
```

Invoice number must be unique.

Example:

```text
INV-20260903-000001
```

---

# 31. Payments

Payment status must come from the payment gateway/server verification.

Do not mark:

```text
payment = paid
```

because the mobile app says payment succeeded.

Expected flow:

```text
Create Payment Intent/Order
        ↓
Customer Payment
        ↓
Gateway Callback/Webhook
        ↓
Server Verification
        ↓
Payment = successful
        ↓
Invoice = paid
```

Webhook handlers must be idempotent.

The same payment webhook must not create duplicate transactions.

---

# 32. Tips

Tip is separate from the main invoice unless business rules explicitly combine them.

Possible flow:

```text
Job Completed
   ↓
Customer pays
   ↓
Customer adds tip
   ↓
Provider earnings updated
```

Never allow a technician to manually mark a tip as paid.

---

# 33. Provider Earnings

Provider earnings must be calculated from verified financial transactions.

Do not calculate wallet balance from UI data.

Recommended:

```text
provider_earnings
```

should reference:

```text
provider
service_request
invoice
payment
tip
```

Use immutable transaction records where possible.

---

# 34. Reviews

Customer can review the provider after job completion.

Optionally support technician review separately if required.

Minimum validation:

```text
customer owns request
request is completed
customer has not already reviewed the same target
```

Prevent duplicate reviews.

---

# 35. Notifications

Notifications must be event-driven.

Examples:

```text
Customer submits request
        ↓
Notify matched providers

Provider submits bid
        ↓
Notify customer

Customer negotiates
        ↓
Notify provider

Customer selects provider
        ↓
Notify provider

Provider assigns technician
        ↓
Notify technician

Technician arrives
        ↓
Notify customer

Quote submitted
        ↓
Notify customer

Additional charge requested
        ↓
Notify customer

Payment completed
        ↓
Notify provider
```

Use Laravel Events/Listeners/Jobs rather than embedding notification logic everywhere.

---

# 36. Push Notifications

Push notifications must be sent asynchronously through queues.

Never block the main API request unnecessarily while sending push notifications.

Store device tokens separately.

A user may have:

```text
multiple devices
multiple sessions
```

Do not assume one user has one device token.

---

# 37. API Architecture

All mobile APIs must be versioned.

Example:

```text
/api/v1/customer/...
/api/v1/technician/...
```

Do not mix customer and technician endpoints without authorization rules.

Example:

```text
/api/v1/customer/requests
/api/v1/customer/bids
/api/v1/customer/negotiations

/api/v1/technician/jobs
/api/v1/technician/jobs/{id}/arrive
/api/v1/technician/jobs/{id}/inspection
/api/v1/technician/jobs/{id}/progress
/api/v1/technician/jobs/{id}/complete
```

Provider web APIs/routes:

```text
/provider/...
```

Admin:

```text
/admin/...
```

---

# 38. API Response Format

Use a consistent JSON format.

Success:

```json
{
  "success": true,
  "message": "Request created successfully.",
  "data": {}
}
```

Validation error:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

Server/business error:

```json
{
  "success": false,
  "message": "This action is not allowed."
}
```

Do not return random response formats between endpoints.

---

# 39. API Resources

Do not expose Eloquent models directly.

Use:

```text
ServiceRequestResource
ProviderResource
TechnicianResource
BidResource
NegotiationResource
QuoteResource
InvoiceResource
PaymentResource
```

Only expose fields required by the client.

Never expose:

```text
password
password_hash
internal tokens
private administrative data
payment secrets
```

---

# 40. Authorization

Use Laravel Policies/Gates.

Every resource action must verify ownership/relationship.

Examples:

Customer can access:

```text
only own service requests
only bids belonging to own request
only negotiations belonging to own request
only own invoices/payments
```

Provider can access:

```text
only requests sent to provider
only its own bids
only its own technicians
only jobs assigned/awarded to provider
```

Technician can access:

```text
only jobs assigned to that technician
```

Admin can access according to admin permissions.

---

# 41. Critical Authorization Rule

Never authorize by only checking:

```php
$request->id
```

Always verify ownership.

Bad:

```php
ServiceRequest::findOrFail($id);
```

followed by returning it to the current user.

Good:

```php
ServiceRequest::where('customer_id', auth()->id())
    ->findOrFail($id);
```

Or use a Policy.

---

# 42. Provider Isolation

Provider A must never see Provider B's:

```text
bids
negotiations
technicians
earnings
private notes
commercial data
```

All provider queries MUST be scoped.

---

# 43. Technician Isolation

Technician A must never access:

```text
Technician B's assigned jobs
Provider B's jobs
Provider B's financial information
Provider B's technicians
```

Unless an explicit admin permission exists.

---

# 44. Customer Privacy

Customers must only see provider information required for service.

Do not expose:

```text
private provider data
internal provider notes
other customers
other bids' private negotiation messages
technician private information
```

---

# 45. Concurrency / Race Conditions

The bidding system is concurrency-sensitive.

Example:

```text
Customer receives Provider A bid
Customer receives Provider B bid
Customer selects A
Customer also selects B
```

The backend must guarantee only one provider becomes selected.

Use:

```text
DB::transaction()
row locking where appropriate
database constraints
```

The same applies to:

```text
quote approval
job completion
payment processing
technician assignment
bid selection
```

---

# 46. Bid Expiration

If bids have an expiration time:

```text
expires_at
```

must be enforced server-side.

An expired bid cannot be accepted.

Use Laravel Scheduler/Queue for automatic expiry, but also validate expiration at request time.

Never depend solely on the scheduled job.

---

# 47. File Uploads

Customer and technician may upload photos.

Store using Laravel Storage.

Recommended categories:

```text
service-requests/
inspections/
job-progress/
materials/
documents/
```

Validate:

```text
mime type
file size
extension
authorization
```

Never trust the client-provided file extension.

Do not expose private storage files directly.

Use signed/authorized URLs where required.

---

# 48. Location

Store:

```text
latitude
longitude
```

where location is required.

Do not treat a formatted address as the authoritative geographic coordinate.

Provider matching must use coordinates.

Technician location updates should be associated with the active job where applicable.

---

# 49. Live Technician Location

If live location is implemented:

```text
Technician App
     ↓
Location API / realtime channel
     ↓
Backend
     ↓
Customer/Admin authorized view
```

Rules:

- Only active-job location should be shared.
- Do not expose technician location after the job when not required.
- Validate technician ownership of the active assignment.
- Do not accept arbitrary technician IDs from the client.

---

# 50. Admin Web Panel

Admin must be able to manage:

```text
Dashboard
Customers
Providers
Technicians
Services
Service Requests
Provider Bids
Negotiations
Assignments
Inspections
Quotes
Jobs
Additional Charges
Invoices
Payments
Tips
Reviews
Notifications
Reports
Settings
```

Admin must be able to inspect the complete lifecycle of a request:

```text
Request
→ Matching
→ Bids
→ Negotiation
→ Selected Provider
→ Technician
→ Inspection
→ Quote
→ Approval
→ Execution
→ Additional Charges
→ Invoice
→ Payment
→ Completion
→ Review
```

---

# 51. Provider Web Panel

Provider panel should support:

```text
Dashboard
Profile
Services
Technicians
Availability
Incoming Requests
Bids
Negotiations
Won Jobs
Technician Assignment
Active Jobs
Quotes
Invoices
Payments
Earnings
Reviews
Notifications
```

Provider can assign/reassign technicians according to job state and authorization rules.

---

# 52. Technician API

Technician mobile APIs should support:

```text
Authentication
Profile
Availability / duty status
Incoming assignments
Job details
Accept assignment
Decline assignment
Navigation
Mark arrived
Inspection
Upload photos
Submit quote data where permitted
Start job
Update progress
Add labor
Add materials
Request additional services
Complete job
View job history
Notifications
```

The technician app architecture includes these responsibilities across job dispatch, navigation/tracking, inspection/quote, execution, invoice/settlement and earnings/history. fileciteturn1file1L34-L42

---

# 53. Customer API

Customer APIs should support:

```text
Authentication
Profile
Addresses
Services
Create request
Upload request photos
Nearby/provider discovery where allowed
Direct provider request
View provider bids
View bid details
Negotiation
Select provider
View technician
Track job
Approve quote
Approve additional charges
Payment
Tip
Invoice
Review
Request history
Notifications
```

---

# 54. Database Design Rules

Use:

```text
bigint / UUID
foreign keys
indexes
unique constraints
timestamps
soft deletes where appropriate
```

Every foreign key must have an intentional delete strategy.

Avoid cascading deletes for financial records.

Recommended indexes include:

```text
service_requests.customer_id
service_requests.service_id
service_requests.status
service_requests.latitude/longitude where supported
provider_job_requests.provider_id
provider_job_requests.status
provider_bids.provider_id
provider_bids.service_request_id
provider_bids.status
job_assignments.provider_id
job_assignments.technician_id
job_assignments.status
payments.invoice_id
payments.status
notifications.user_id
```

---

# 55. Recommended Core Entities

The backend should maintain clear entities for:

```text
User
Customer
Provider
ProviderProfile
Technician
TechnicianProfile
Service
ProviderService
CustomerAddress
ServiceRequest
RequestPhoto
ProviderJobRequest
ProviderBid
BidOffer / Negotiation
JobAssignment
JobInspection
Quote
QuoteItem
JobLabor
JobMaterial
AdditionalService
CustomerApproval
JobProgress
JobStatusHistory
Invoice
InvoiceItem
Payment
Tip
ProviderEarning
Review
Notification
DeviceToken
```

If the existing database already contains an equivalent table, extend it instead of creating a duplicate concept.

---

# 56. Existing Schema Compatibility

The existing schema contains concepts including:

```text
service_requests
provider_job_requests
provider_services
provider_profiles
job_assignments
job_inspections
quotes
quote_items
job_labor
job_materials
additional_services
customer_approvals
job_progress
job_status_history
invoices
invoice_items
payments
tips
provider_earnings
reviews
notifications
```

Preserve these concepts where they fit the final business workflow.

Do not create duplicate tables such as:

```text
jobs
service_jobs
customer_jobs
provider_jobs
```

just because an AI agent prefers a different name.

Only introduce a new entity when the business requirement cannot be represented cleanly by the existing model.

---

# 57. Required New Bid/Negotiation Concept

The broadcast + bidding + negotiation requirement MUST be represented explicitly.

Do not misuse:

```text
quotes
```

as the provider's initial marketplace bid if the business process treats bids and job quotes as separate stages.

Preferred conceptual separation:

```text
Provider Bid
    ↓
Customer Negotiation
    ↓
Accepted Bid
    ↓
Inspection
    ↓
Technical Quote / Scope
    ↓
Customer Approval
    ↓
Job
```

This separation prevents marketplace bidding history from being confused with the post-inspection work quote.

---

# 58. Events

Use domain/application events such as:

```text
ServiceRequestCreated
ProvidersMatched
ProviderBidSubmitted
BidOfferSubmitted
BidAccepted
ProviderSelected
TechnicianAssigned
TechnicianAcceptedJob
TechnicianArrived
InspectionCompleted
QuoteSubmitted
QuoteApproved
JobStarted
AdditionalChargeRequested
AdditionalChargeApproved
JobCompleted
InvoiceGenerated
PaymentSuccessful
TipAdded
ReviewSubmitted
```

Listeners can handle:

```text
notifications
push messages
analytics
audit records
queue jobs
```

---

# 59. Jobs / Queues

Queue operations such as:

```text
SendPushNotification
SendEmailNotification
MatchProviders
ExpireProviderRequests
ExpireBids
GenerateInvoice
ProcessPaymentWebhook
UpdateProviderEarnings
```

Do not queue operations that must return an immediate authoritative transaction result unless the API contract supports asynchronous processing.

---

# 60. Transactions

Use database transactions for:

### Selecting a bid

```text
lock request
verify request is selectable
verify bid is valid
select provider
close competing bids
create/update assignment
commit
```

### Approving quote

```text
verify quote
create approval
change job state
commit
```

### Completing payment

```text
verify gateway
create payment
update invoice
update earnings
commit
```

### Assigning technician

```text
verify provider
verify technician
verify technician belongs to provider
verify technician availability
create assignment
commit
```

---

# 61. Validation

Use dedicated Form Request classes.

Examples:

```text
CreateServiceRequest
SubmitProviderBidRequest
CreateNegotiationOfferRequest
SelectBidRequest
AssignTechnicianRequest
SubmitInspectionRequest
SubmitQuoteRequest
ApproveQuoteRequest
AddLaborRequest
AddMaterialRequest
RequestAdditionalChargeRequest
ApproveAdditionalChargeRequest
CompleteJobRequest
CreatePaymentRequest
SubmitReviewRequest
```

Do not duplicate validation rules across controllers.

---

# 62. Money Handling

Never use floating-point arithmetic for money.

Use:

```text
DECIMAL
```

in MySQL.

In PHP, use decimal-safe calculations.

Example database type:

```text
DECIMAL(12,2)
```

Never use:

```text
float
```

for financial calculations.

---

# 63. Tax / Discount

Tax and discounts must be calculated server-side.

Store the actual applied values on the transaction/invoice.

Do not depend on current global tax settings to reconstruct historical invoices.

Historical invoices must remain reproducible.

---

# 64. Soft Delete

Use soft deletes for entities where historical relationships matter:

```text
Provider
Technician
Service
Customer
```

Do not hard-delete:

```text
payments
invoices
earnings
status history
accepted bids
audit records
```

---

# 65. Logging

Log important failures without exposing secrets.

Never log:

```text
passwords
OTP
API secrets
payment secrets
access tokens
refresh tokens
```

Log:

```text
request ID
user ID
route
exception
business entity ID
timestamp
```

---

# 66. API Security

Use:

```text
Laravel Sanctum
rate limiting
authorization policies
validation
CSRF for web sessions
secure file validation
webhook signature verification
```

Authentication and authorization are different.

Being logged in does NOT mean the user can access every resource.

---

# 67. Idempotency

Critical operations must support idempotency where duplicate requests are possible.

Especially:

```text
payment
bid selection
quote approval
additional charge approval
job completion
technician assignment
```

Example:

```text
Idempotency-Key
```

may be used for payment/transaction APIs.

---

# 68. Webhooks

Payment webhooks must:

```text
verify signature
identify transaction
check existing transaction
process only once
return appropriate HTTP response
```

Never trust:

```text
amount
currency
status
user ID
```

from an unsigned/unverified webhook.

---

# 69. API Pagination

List APIs must be paginated.

Examples:

```text
customer request history
provider incoming requests
provider bids
technician jobs
notifications
earnings
reviews
```

Never return thousands of records in one API response.

---

# 70. API Filtering

Use server-side filtering:

```text
status
date
service
provider
technician
location/radius
```

Do not download all records to the mobile application and filter locally.

---

# 71. Performance

Use eager loading where appropriate.

Avoid N+1 queries.

Example:

```php
ServiceRequest::with([
    'customer',
    'service',
    'address',
    'providerBids',
])
```

Do not blindly eager-load huge relationship trees.

Use query scopes and indexes.

---

# 72. Caching

Cache relatively static data:

```text
services
service categories
configuration
```

Do NOT blindly cache highly dynamic data such as:

```text
bid status
payment status
active job state
technician assignment
```

unless cache invalidation is explicitly implemented.

---

# 73. Testing

Every business-critical workflow must have automated tests.

Minimum test coverage:

```text
Customer creates request
Broadcast matching
Direct provider request
Provider receives request
Provider submits bid
Customer receives bids
Customer negotiates
Customer selects bid
Provider assigns technician
Technician accepts
Technician marks arrived
Inspection
Quote
Customer approval
Job start
Additional charge
Additional charge approval
Job completion
Invoice
Payment
Tip
Review
```

---

# 74. Authorization Tests

Test that:

```text
Customer A cannot access Customer B request
Provider A cannot access Provider B bid
Provider A cannot access Provider B technician
Technician A cannot access Technician B job
Technician cannot access provider financial APIs
Customer cannot modify provider bid
Customer cannot modify technician assignment
Provider cannot modify another provider's negotiation
```

These tests are mandatory.

---

# 75. State Transition Tests

Every invalid state transition must have a test.

Examples:

```text
completed → in_progress
paid → quote_pending
inspection → completed
unassigned → technician_completed
expired_bid → accepted
rejected_bid → accepted
```

All must fail.

---

# 76. API Documentation

Document every API.

Recommended:

```text
OpenAPI / Swagger
```

Document:

```text
endpoint
method
authentication
request parameters
request body
response
validation errors
authorization rules
status codes
```

---

# 77. Error Handling

Use domain-specific exceptions where appropriate.

Examples:

```text
InvalidStateTransitionException
UnauthorizedResourceException
BidExpiredException
BidAlreadySelectedException
TechnicianUnavailableException
QuoteNotApprovedException
PaymentVerificationException
```

Do not expose internal stack traces in production APIs.

---

# 78. HTTP Status Codes

Use consistent status codes:

```text
200 -> successful request
201 -> resource created
204 -> successful no-content operation
400 -> invalid request
401 -> unauthenticated
403 -> unauthorized
404 -> resource not found
409 -> business conflict
422 -> validation error
429 -> rate limit
500 -> server error
```

---

# 79. Admin Overrides

Admin may have controlled override capabilities.

Every admin override MUST:

```text
require permission
record reason
create audit record
```

Never silently modify financial/workflow data.

---

# 80. Environment Configuration

Never hardcode:

```text
database credentials
Firebase keys
payment secrets
mail credentials
API keys
JWT/Sanctum secrets
```

Use:

```text
.env
config/*.php
```

Never commit `.env`.

---

# 81. Coding Rules for AI Agents

Before modifying code:

1. Inspect existing implementation.
2. Inspect migrations.
3. Inspect models and relationships.
4. Inspect existing routes.
5. Inspect existing API contracts.
6. Reuse existing architecture.
7. Only then implement the change.

Do not rewrite working modules unnecessarily.

Do not rename database fields merely for stylistic preference.

Do not create duplicate services/models/controllers.

---

# 82. Migration Rules

Every schema modification requires a migration.

Never manually modify production database structure without a migration.

Migration must include:

```text
foreign keys
indexes
unique constraints
nullable rules
defaults
```

Test both:

```text
php artisan migrate
php artisan migrate:fresh --seed
```

where safe in development.

---

# 83. Seeders

Seeders should provide development/test data for:

```text
admin
customer
provider
technicians
services
provider services
sample requests
sample bids
sample jobs
```

Do not seed real payment credentials.

---

# 84. API Versioning

Do not break existing mobile clients silently.

When a breaking API change is required:

```text
/api/v1
/api/v2
```

Use a new version when necessary.

Do not modify an existing response structure without checking client compatibility.

---

# 85. Business Workflow Summary

The canonical workflow is:

```text
CUSTOMER
    ↓
Create Service Request
    ↓
Choose:
    ├── Broadcast to nearby providers
    └── Direct request to one provider
    ↓
PROVIDER
    ↓
Receive request
    ↓
Submit bid
    ↓
CUSTOMER
    ↓
Compare bids
    ↓
Negotiate
    ↓
Select provider/bid
    ↓
PROVIDER
    ↓
Assign technician
    ↓
TECHNICIAN
    ↓
Accept assignment
    ↓
En Route
    ↓
Arrive
    ↓
Inspect
    ↓
Prepare/submit quote
    ↓
CUSTOMER
    ↓
Approve quote
    ↓
TECHNICIAN
    ↓
Start Job
    ↓
Add labor/materials
    ↓
Request additional charges if needed
    ↓
CUSTOMER
    ↓
Approve/reject additional charges
    ↓
TECHNICIAN
    ↓
Complete Job
    ↓
BACKEND
    ↓
Generate Final Invoice
    ↓
CUSTOMER
    ↓
Payment
    ↓
Tip
    ↓
Review
    ↓
PROVIDER
    ↓
Earnings Updated
```

---

# 86. Definition of Done

A backend feature is NOT complete until:

```text
[ ] Migration created/updated
[ ] Model created/updated
[ ] Relationships implemented
[ ] Enum/status implemented
[ ] Form Request validation implemented
[ ] Service/business logic implemented
[ ] Policy/authorization implemented
[ ] Controller implemented
[ ] API Resource implemented
[ ] Routes implemented
[ ] Notifications/events handled where required
[ ] Audit/history handled where required
[ ] Transaction handling implemented where required
[ ] Error handling implemented
[ ] Tests written
[ ] API documentation updated
[ ] Existing mobile API compatibility checked
```

---

# 87. Final AI Agent Instruction

When implementing any feature, ALWAYS ask:

```text
1. Which actor is performing this action?
2. Does the actor own/have access to this resource?
3. What is the current state?
4. Is the requested state transition valid?
5. Does this action require a database transaction?
6. Does this action affect money?
7. Does this action require customer/provider/technician notification?
8. Does this action require audit history?
9. Could two requests execute this action simultaneously?
10. Could the mobile client be sending manipulated values?
```

If any answer indicates a business rule, implement that rule on the backend.

## Golden Rule

**The mobile application and web panels are clients. The Laravel backend owns the business logic, authorization, workflow state, pricing, bidding, negotiation, assignment, payment verification and financial records.**

Never trust the client.

Never bypass the state machine.

Never expose another actor's private data.

Never overwrite financial or negotiation history.

Never allow a technician to act outside their provider.

Never allow two providers to win the same job.

Never mark payment successful without server-side verification.
