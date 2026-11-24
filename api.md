# On-Demand Service Platform API Documentation (v1)

Welcome to the official RESTful API documentation for the On-Demand Service Platform. All endpoints process requests and responses using standard JSON. Protected endpoints require a Laravel Sanctum Bearer Token.

---

## Table of Contents
1. [General Setup & Headers](#1-general-setup--headers)
2. [Authentication APIs](#2-authentication-apis)
   - [POST /api/v1/auth/register](#post-apiv1authregister)
   - [POST /api/v1/auth/login](#post-apiv1authlogin)
   - [GET /api/v1/auth/me](#get-apiv1authme)
   - [POST /api/v1/auth/logout](#post-apiv1authlogout)
3. [Customer APIs](#3-customer-apis)
   - [GET /api/v1/customer/requests](#get-apiv1customerrequests)
   - [POST /api/v1/customer/requests](#post-apiv1customerrequests)
   - [GET /api/v1/customer/requests/{id}](#get-apiv1customerrequestsid)
   - [GET /api/v1/customer/requests/{id}/bids](#get-apiv1customerrequestsidbids)
   - [POST /api/v1/customer/bids/{bidId}/negotiate](#post-apiv1customerbidsbididnegotiate)
   - [POST /api/v1/customer/bids/{bidId}/select](#post-apiv1customerbidsbididselect)
   - [POST /api/v1/customer/quotes/{quoteId}/approve](#post-apiv1customerquotesquoteidapprove)
   - [Two-phase payments (Stripe)](#two-phase-payments-stripe)
   - [POST /api/v1/customer/requests/{id}/payment-intent](#post-apiv1customerrequestsidpayment-intent)
   - [POST /api/v1/customer/requests/{id}/payment-confirm](#post-apiv1customerrequestsidpayment-confirm)
   - [GET /api/v1/customer/requests/{id}/payment-summary](#get-apiv1customerrequestsidpayment-summary)
   - [POST /api/v1/stripe/webhook](#post-apiv1stripewebhook)
4. [Service Provider APIs](#4-service-provider-apis)
   - [GET /api/v1/provider/requests](#get-apiv1providerrequests)
   - [POST /api/v1/provider/requests/{requestId}/bid](#post-apiv1providerrequestsrequestidbid)
   - [POST /api/v1/provider/requests/{requestId}/assign-technician](#post-apiv1providerrequestsrequestidassign-technician)
   - [GET /api/v1/provider/earnings](#get-apiv1providerearnings)
5. [Field Technician APIs](#5-field-technician-apis)
   - [GET /api/v1/technician/jobs](#get-apiv1technicianjobs)
   - [GET /api/v1/technician/jobs/{assignmentId}](#get-apiv1technicianjobsassignmentid)
   - [POST /api/v1/technician/jobs/{assignmentId}/accept](#post-apiv1technicianjobsassignmentidaccept)
   - [POST /api/v1/technician/jobs/{assignmentId}/arrive](#post-apiv1technicianjobsassignmentidarrive)
   - [POST /api/v1/technician/jobs/{assignmentId}/inspection](#post-apiv1technicianjobsassignmentidinspection)
   - [POST /api/v1/technician/jobs/{assignmentId}/progress](#post-apiv1technicianjobsassignmentidprogress)
   - [POST /api/v1/technician/jobs/{assignmentId}/complete](#post-apiv1technicianjobsassignmentidcomplete)

---

## 1. General Setup & Headers

### Base URL
`http://localhost:8000/api/v1`

### Required Headers
For **Unprotected Endpoints**:
```http
Content-Type: application/json
Accept: application/json
```

For **Protected Endpoints** (requires Bearer Token):
```http
Authorization: Bearer <YOUR_SANCTUM_TOKEN>
Content-Type: application/json
Accept: application/json
```

---


### Timezones
- All timestamps in responses are **UTC**, ISO 8601 (e.g. `2026-11-04T23:00:00+00:00`). Apps convert them to the device zone for display.
- `preferred_date` (`YYYY-MM-DD`) and `preferred_start_time` / `preferred_end_time` (`HH:MM`) are the customer's **local wall-clock** values and are never converted.
- Apps send `X-Timezone: <IANA zone>` (e.g. `America/Chicago`) on every request. The backend uses it for "today" / "this week" filters and saves it as the booking's `timezone` when a request is created (an explicit `timezone` field in `POST /customer/requests` wins). Unknown values are ignored.
- Business rules for a booking (e.g. technician assignment unlocking on `preferred_date`) use the booking's `timezone`. Web panels, emails and PDFs use `DISPLAY_TIMEZONE` (default `America/New_York`).

## 2. Authentication APIs

### POST `/api/v1/auth/login`
- **Scenario**: User (Customer, Provider, or Technician) logs in with email and password to obtain a Sanctum access token.
- **Headers**: `Content-Type: application/json`, `Accept: application/json`
- **Payload Schema**:
  - `email` *(string, required)*: Registered user email address.
  - `password` *(string, required)*: User account password.
- **Request Example**:
```json
{
  "email": "customer@example.com",
  "password": "password123"
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "1|Q8K9zXwY0M1a2b3c4d5e6f7g8h9i0j1k2l3m4n5o",
    "user": {
      "id": 5,
      "name": "Jane Doe",
      "email": "customer@example.com",
      "role": "customer",
      "phone": "+15551234567",
      "avatar": null,
      "is_active": true,
      "created_at": "2026-09-04T12:00:00.000000Z"
    }
  }
}
```
- **Error Response (`401 Unauthorized`)**:
```json
{
  "success": false,
  "message": "Invalid email or password credentials."
}
```

---

### GET `/api/v1/auth/me`
- **Scenario**: App startup check to verify active Bearer token and retrieve the logged-in user profile.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "User profile retrieved.",
  "data": {
    "id": 5,
    "name": "Jane Doe",
    "email": "customer@example.com",
    "role": "customer",
    "phone": "+15551234567",
    "avatar": null,
    "is_active": true,
    "created_at": "2026-09-04T12:00:00.000000Z"
  }
}
```

---

### POST `/api/v1/auth/logout`
- **Scenario**: Revokes the user's current Sanctum access token upon logging out.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

## 3. Customer APIs

### GET `/api/v1/customer/requests`
- **Scenario**: Customer retrieves a paginated list of their own service requests.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Query Parameters**: `page` *(integer, optional, default: 1)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Service requests retrieved successfully.",
  "data": [
    {
      "id": 12,
      "request_number": "SR-20260905-8812",
      "customer_id": 5,
      "service_category_id": 1,
      "category": {
        "id": 1,
        "name": "Plumbing",
        "slug": "plumbing",
        "icon": "plumbing",
        "image": null
      },
      "service_id": null,
      "service": null,
      "request_type": "broadcast",
      "description": "Kitchen sink pipe dripping heavily under the counter.",
      "address_line": "742 Evergreen Terrace, Sector 4",
      "latitude": 28.6139,
      "longitude": 77.209,
      "preferred_date": "2026-09-06",
      "priority": "normal",
      "status": "submitted",
      "created_at": "2026-09-05T00:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 1
  }
}
```

---

### POST `/api/v1/customer/requests`
- **Scenario**: Customer selects a service category and specifies job requirements (address, description, date/time) to broadcast a job request or target a specific provider directly.
- **Deposit**: The request is created with status `pending_payment` and a server-computed `deposit_amount` (the sub-category / category minimum booking charge). It is **not** sent to providers until the deposit is paid via [`payment-intent`](#post-apiv1customerrequestsidpayment-intent) with `type: "deposit"`.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `service_category_id` *(integer, required)*: Valid category ID from `service_categories` table.
  - `service_id` *(integer, optional)*: Optional specific service ID.
  - `request_type` *(string, required)*: Must be `'broadcast'` or `'direct'`.
  - `target_provider_id` *(integer, required_if: request_type=direct)*: Target provider user ID when direct.
  - `description` *(string, required, max: 2000)*: Detailed job requirements.
  - `address_line` *(string, required, max: 500)*: Customer location address line.
  - `latitude` *(number, required, between -90 and 90)*: Location latitude.
  - `longitude` *(number, required, between -180 and 180)*: Location longitude.
  - `preferred_date` *(string, required, format: YYYY-MM-DD, after_or_equal: today)*: Preferred service date.
  - `preferred_start_time` *(string, optional, format: HH:MM)*: Window start time.
  - `preferred_end_time` *(string, optional, format: HH:MM)*: Window end time.
  - `priority` *(string, optional, in: low, normal, high, urgent)*: Priority level.
  - `timezone` *(string, optional, IANA zone e.g. `America/Chicago`)*: Customer's zone. Defaults to the `X-Timezone` header, then `DISPLAY_TIMEZONE`.
- **Request Example**:
```json
{
  "service_category_id": 1,
  "request_type": "broadcast",
  "target_provider_id": null,
  "description": "Kitchen sink pipe dripping heavily under the counter.",
  "address_line": "742 Evergreen Terrace, Sector 4",
  "latitude": 28.6139,
  "longitude": 77.2090,
  "preferred_date": "2026-09-06",
  "preferred_start_time": "10:00",
  "preferred_end_time": "12:00",
  "priority": "normal"
}
```
- **Success Response (`201 Created`)**:
```json
{
  "success": true,
  "message": "Service request created. Pay the booking deposit to confirm it.",
  "data": {
    "id": 12,
    "request_number": "SR-20260905-8812",
    "customer_id": 5,
    "service_category_id": 1,
    "category": {
      "id": 1,
      "name": "Plumbing",
      "slug": "plumbing",
      "icon": "plumbing",
      "image": null
    },
    "service_id": null,
    "service": null,
    "request_type": "broadcast",
    "description": "Kitchen sink pipe dripping heavily under the counter.",
    "address_line": "742 Evergreen Terrace, Sector 4",
    "latitude": 28.6139,
    "longitude": 77.209,
    "preferred_date": "2026-09-06",
    "priority": "normal",
    "status": "pending_payment",
    "payment_status": "deposit_pending",
    "deposit_amount": 50.0,
    "deposit_paid": false,
    "deposit_paid_at": null,
    "upfront_deposit_paid": 0.0,
    "invoice_id": null,
    "invoice_total": null,
    "balance_due": 0.0,
    "created_at": "2026-09-05T00:00:00.000000Z"
  }
}
```
- **Payment fields on every ServiceRequest resource**: `payment_status` is `deposit_pending` → `deposit_paid` → `paid`. `upfront_deposit_paid` is the deposit actually collected (0 until paid). `balance_due` is invoice total (excluding tip) minus the paid deposit.
- **Validation Error Response (`422 Unprocessable Content`)**:
```json
{
  "message": "The service category id field is required.",
  "errors": {
    "service_category_id": ["The service category id field is required."]
  }
}
```

---

### GET `/api/v1/customer/requests/{id}`
- **Scenario**: Customer views detailed information for a single service request.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Request details retrieved.",
  "data": {
    "id": 12,
    "request_number": "SR-20260905-8812",
    "customer_id": 5,
    "service_category_id": 1,
    "category": {
      "id": 1,
      "name": "Plumbing",
      "slug": "plumbing",
      "icon": "plumbing",
      "image": null
    },
    "service_id": null,
    "service": null,
    "request_type": "broadcast",
    "description": "Kitchen sink pipe dripping heavily under the counter.",
    "address_line": "742 Evergreen Terrace, Sector 4",
    "latitude": 28.6139,
    "longitude": 77.209,
    "preferred_date": "2026-09-06",
    "priority": "normal",
    "status": "submitted",
    "created_at": "2026-09-05T00:00:00.000000Z"
  }
}
```

---

### GET `/api/v1/customer/requests/{id}/bids`
- **Scenario**: Customer inspects bids and price proposals submitted by service providers.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Bids retrieved.",
  "data": [
    {
      "id": 4,
      "service_request_id": 12,
      "provider_id": 8,
      "provider": {
        "id": 8,
        "name": "Apex Plumbing Services",
        "email": "apex@example.com",
        "role": "provider",
        "phone": "+15559876543",
        "avatar": null,
        "is_active": true,
        "created_at": "2026-09-01T10:00:00.000000Z"
      },
      "amount": 140.0,
      "estimated_duration": "2 hours",
      "availability_date": "2026-09-06",
      "message": "We can dispatch a plumber tomorrow morning.",
      "status": "submitted",
      "expires_at": null,
      "created_at": "2026-09-05T00:01:00.000000Z"
    }
  ]
}
```

---

### POST `/api/v1/customer/bids/{bidId}/negotiate`
- **Scenario**: Customer submits a price counter-offer on a provider's bid.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `amount` *(number, required, min: 1)*: Counter-offer price amount.
  - `message` *(string, optional)*: Counter-offer note.
- **Request Example**:
```json
{
  "amount": 125.00,
  "message": "Can you do $125 if we schedule for 10:00 AM?"
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Counter offer submitted successfully.",
  "data": {
    "id": 9,
    "provider_bid_id": 4,
    "sender_type": "customer",
    "sender_id": 5,
    "amount": 125.0,
    "message": "Can you do $125 if we schedule for 10:00 AM?",
    "status": "pending",
    "updated_at": "2026-09-05T00:02:00.000000Z",
    "created_at": "2026-09-05T00:02:00.000000Z"
  }
}
```

---

### POST `/api/v1/customer/bids/{bidId}/select`
- **Scenario**: Customer accepts a provider's bid. Automatically marks the winning bid as `'accepted'`, sets request status to `'awarded'`, and marks competitor bids as `'rejected'`.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Provider bid selected successfully."
}
```

---

### POST `/api/v1/customer/quotes/{quoteId}/approve`
- **Scenario**: Customer approves a post-inspection commercial quote generated by provider/technician.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Commercial quote approved successfully.",
  "data": {
    "id": 3,
    "service_request_id": 12,
    "provider_id": 8,
    "technician_id": 15,
    "labor_subtotal": 100.0,
    "material_subtotal": 25.0,
    "tax": "6.25",
    "discount": 0.0,
    "total": 131.25,
    "notes": "P-trap replacement labor and parts.",
    "status": "approved",
    "created_at": "2026-09-05T00:03:00.000000Z"
  }
}
```

---

### Two-phase payments (Stripe)
Customers pay online only (no cash). Every amount is computed by the backend; the app never sends an amount.

| Phase | When | Amount |
|---|---|---|
| `deposit` | Right after `POST /customer/requests` | `deposit_amount` (category minimum booking charge) |
| `balance` | After the technician completes the job (invoice issued) | invoice total − deposit paid + optional tip |

Flow for each phase:
1. `POST /customer/requests/{id}/payment-intent` → returns a Stripe `client_secret` and `publishable_key`.
2. App presents the Stripe PaymentSheet with that `client_secret`.
3. `POST /customer/requests/{id}/payment-confirm` with the `payment_intent_id`. The backend re-fetches the PaymentIntent from Stripe and applies it. The Stripe webhook applies the same result independently; both are idempotent.

On deposit success the request moves `pending_payment` → `submitted` and is sent to providers. On balance success the invoice becomes `paid` and provider earnings / tip are recorded.

The old `POST /customer/requests/{id}/pay`, `POST /customer/invoices/{invoiceId}/pay` and technician `POST /technician/jobs/{id}/settle` endpoints were removed.

---

### POST `/api/v1/customer/requests/{id}/payment-intent`
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `type` *(string, required, in: `deposit`, `balance`)*
  - `tip_amount` *(number, optional, min: 0; balance only)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Payment intent ready.",
  "data": {
    "requires_payment": true,
    "gateway": "stripe",
    "payment_type": "balance",
    "payment_id": 14,
    "payment_intent_id": "pi_3QabcXYZ",
    "client_secret": "pi_3QabcXYZ_secret_123",
    "publishable_key": "pk_live_...",
    "amount": 160.0,
    "currency": "usd",
    "breakdown": { "invoice_id": 9, "balance_due": 150.0, "tip_amount": 10.0 },
    "service_request_id": 12
  }
}
```
- Calling it again for the same amount reuses the open PaymentIntent; a different tip cancels the old one and creates a new one.
- If the deposit already covers the invoice and no tip is added, `requires_payment` is `false`, the invoice is marked paid, and the payment summary fields are returned instead.
- **Errors (`422`)**: deposit already paid, request not in `pending_payment`, balance requested before the job is completed, or already paid in full.

---

### POST `/api/v1/customer/requests/{id}/payment-confirm`
- **Payload Schema**: `payment_intent_id` *(string, required)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Deposit received. Your request has been sent to providers.",
  "data": {
    "payment_id": 13,
    "payment_type": "deposit",
    "amount": 50.0,
    "summary": { "...": "same shape as payment-summary" }
  }
}
```
- **`409 Conflict`**: the PaymentIntent has not succeeded yet (e.g. the sheet was closed or 3-D Secure is still pending). Retry or poll `payment-summary`.

---

### GET `/api/v1/customer/requests/{id}/payment-summary`
```json
{
  "success": true,
  "data": {
    "service_request_id": 12,
    "currency": "usd",
    "deposit_amount": 50.0,
    "deposit_paid": true,
    "deposit_paid_at": "2026-11-24T10:00:00+00:00",
    "invoice_id": 9,
    "invoice_total": 200.0,
    "balance_due": 150.0,
    "can_pay_balance": true,
    "is_paid": false,
    "payment_status": "deposit_paid"
  }
}
```

---

### POST `/api/v1/stripe/webhook`
- Public endpoint for Stripe, verified with the `Stripe-Signature` header (`STRIPE_WEBHOOK_SECRET`). Handles `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`. Responds `400` for an invalid signature.
- Backend env: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_CURRENCY` (default `usd`).

#### Fake payment mode (development, before a Stripe account exists)
- `PAYMENT_DRIVER=fake` makes every PaymentIntent succeed instantly with no Stripe keys. `payment-intent` returns `"gateway": "fake"` and `publishable_key: null`; the app skips the payment sheet and calls `payment-confirm` directly. Payment rows are stored with `payment_gateway = "fake"`.
- Refused when `APP_ENV=production` unless `PAYMENT_ALLOW_FAKE_IN_PRODUCTION=true` (only for a non-public test server).
- Switch to real payments with `PAYMENT_DRIVER=stripe` plus the `STRIPE_*` keys; no app changes needed.

---

## 4. Service Provider APIs

### GET `/api/v1/provider/requests`
- **Scenario**: Provider retrieves a paginated list of incoming job invitations dispatched to their organization within service radius.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Query Parameters**: `page` *(integer, optional, default: 1)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Incoming job requests retrieved.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 18,
        "service_request_id": 12,
        "provider_id": 8,
        "provider_profile_id": 3,
        "status": "sent",
        "sent_at": "2026-09-05T00:00:00.000000Z",
        "service_request": {
          "id": 12,
          "request_number": "SR-20260905-8812",
          "description": "Kitchen sink pipe dripping heavily under the counter.",
          "category": {
            "id": 1,
            "name": "Plumbing"
          },
          "customer": {
            "id": 5,
            "name": "Jane Doe",
            "phone": "+15551234567"
          }
        }
      }
    ],
    "last_page": 1,
    "total": 1
  }
}
```

---

### POST `/api/v1/provider/requests/{requestId}/bid`
- **Scenario**: Provider submits a price bid/proposal for an open service request.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `amount` *(number, required, min: 1)*: Proposed bid price.
  - `estimated_duration` *(string, optional, max: 255)*: Estimated job duration (e.g. `'2 hours'`).
  - `availability_date` *(string, optional, format: YYYY-MM-DD, after_or_equal: today)*: Availability date.
  - `message` *(string, optional, max: 1000)*: Proposal message for customer.
- **Request Example**:
```json
{
  "amount": 140.00,
  "estimated_duration": "2 hours",
  "availability_date": "2026-09-06",
  "message": "We can dispatch a licensed plumber tomorrow morning."
}
```
- **Success Response (`201 Created`)**:
```json
{
  "success": true,
  "message": "Bid submitted successfully.",
  "data": {
    "id": 4,
    "service_request_id": 12,
    "provider_id": 8,
    "amount": 140.0,
    "estimated_duration": "2 hours",
    "availability_date": "2026-09-06",
    "message": "We can dispatch a licensed plumber tomorrow morning.",
    "status": "submitted",
    "created_at": "2026-09-05T00:01:00.000000Z"
  }
}
```

---

### POST `/api/v1/provider/requests/{requestId}/assign-technician`
- **Scenario**: Provider assigns an active technician employed by their organization to an awarded job request.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `technician_id` *(integer, required)*: User ID of active & approved technician belonging to provider.
- **Request Example**:
```json
{
  "technician_id": 15
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Technician assigned successfully.",
  "data": {
    "id": 8,
    "service_request_id": 12,
    "provider_id": 8,
    "technician_id": 15,
    "assigned_at": "2026-09-05T00:06:00.000000Z",
    "status": "assigned",
    "updated_at": "2026-09-05T00:06:00.000000Z",
    "created_at": "2026-09-05T00:06:00.000000Z"
  }
}
```

---

### GET `/api/v1/provider/earnings`
- **Scenario**: Provider views earnings ledger showing gross revenue, platform fee deductions (based on provider's custom commission rate), and net payout per job.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Query Parameters**: `page` *(integer, optional, default: 1)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Earnings ledger retrieved.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 2,
        "provider_id": 8,
        "service_request_id": 12,
        "invoice_id": 2,
        "gross_amount": "131.25",
        "platform_fee": "13.13",
        "net_amount": "118.12",
        "created_at": "2026-09-05T00:06:00.000000Z",
        "service_request": {
          "id": 12,
          "request_number": "SR-20260905-8812"
        }
      }
    ],
    "last_page": 1,
    "total": 1
  }
}
```

---

## 5. Field Technician APIs

### GET `/api/v1/technician/jobs`
- **Scenario**: Field technician retrieves a paginated list of jobs assigned to them.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Query Parameters**: `page` *(integer, optional, default: 1)*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Assigned jobs retrieved.",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 8,
        "service_request_id": 12,
        "provider_id": 8,
        "technician_id": 15,
        "assigned_at": "2026-09-05T00:06:00.000000Z",
        "status": "assigned",
        "service_request": {
          "id": 12,
          "request_number": "SR-20260905-8812",
          "address_line": "742 Evergreen Terrace, Sector 4",
          "description": "Kitchen sink pipe dripping heavily under the counter.",
          "category": {
            "id": 1,
            "name": "Plumbing"
          },
          "customer": {
            "id": 5,
            "name": "Jane Doe",
            "phone": "+15551234567"
          }
        }
      }
    ],
    "last_page": 1,
    "total": 1
  }
}
```

---

### GET `/api/v1/technician/jobs/{assignmentId}`
- **Scenario**: Field technician retrieves complete specifications for a single assigned job, including customer contact, location address, job site photos, and current assignment status.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Job assignment details retrieved.",
  "data": {
    "id": 8,
    "service_request_id": 12,
    "provider_id": 8,
    "technician_id": 15,
    "assigned_at": "2026-09-05T00:06:00.000000Z",
    "accepted_at": "2026-09-05T00:07:00.000000Z",
    "arrived_at": null,
    "started_at": null,
    "completed_at": null,
    "status": "accepted",
    "service_request": {
      "id": 12,
      "request_number": "SR-20260905-8812",
      "description": "Kitchen sink pipe dripping heavily under the counter.",
      "address_line": "742 Evergreen Terrace, Sector 4",
      "latitude": 28.6139,
      "longitude": 77.209,
      "preferred_date": "2026-09-06",
      "category": {
        "id": 1,
        "name": "Plumbing",
        "slug": "plumbing",
        "icon": "plumbing",
        "image": null
      },
      "customer": {
        "id": 5,
        "name": "Jane Doe",
        "phone": "+15551234567"
      },
      "photos": []
    }
  }
}
```

---

### POST `/api/v1/technician/jobs/{assignmentId}/accept`
- **Scenario**: Field technician accepts the assigned job dispatch.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Job assignment accepted.",
  "data": {
    "id": 8,
    "service_request_id": 12,
    "status": "accepted",
    "accepted_at": "2026-09-05T00:07:00.000000Z"
  }
}
```

---

### POST `/api/v1/technician/jobs/{assignmentId}/arrive`
- **Scenario**: Field technician arrives at job location and logs arrival timestamp.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Arrived on site logged.",
  "data": {
    "id": 8,
    "service_request_id": 12,
    "status": "arrived",
    "arrived_at": "2026-09-05T00:08:00.000000Z"
  }
}
```

---

### POST `/api/v1/technician/jobs/{assignmentId}/inspection`
- **Scenario**: Technician performs physical site inspection, records technical notes, estimated labor hours, and material costs.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `technical_notes` *(string, required)*: Detailed inspection findings.
  - `estimated_labor_hours` *(number, required, min: 0)*: Estimated labor hours needed.
  - `estimated_material_cost` *(number, required, min: 0)*: Estimated parts/material cost.
  - `photos` *(array of strings, optional)*: Image URLs of inspected site.
- **Request Example**:
```json
{
  "technical_notes": "Main P-trap joint cracked. Requires PVC joint replacement.",
  "estimated_labor_hours": 1.5,
  "estimated_material_cost": 25.00,
  "photos": [
    "https://storage.example.com/inspections/photo1.jpg"
  ]
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Inspection findings submitted successfully.",
  "data": {
    "id": 1,
    "service_request_id": 12,
    "job_assignment_id": 8,
    "technician_id": 15,
    "technical_notes": "Main P-trap joint cracked. Requires PVC joint replacement.",
    "estimated_labor_hours": 1.5,
    "estimated_material_cost": 25.0,
    "photos": [
      "https://storage.example.com/inspections/photo1.jpg"
    ],
    "created_at": "2026-09-05T00:08:30.000000Z"
  }
}
```

---

### POST `/api/v1/technician/jobs/{assignmentId}/progress`
- **Scenario**: Technician updates execution progress percentage and work log.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `progress_percentage` *(integer, required, between 0 and 100)*: Progress percentage.
  - `description` *(string, optional)*: Work log progress notes.
  - `photos` *(array of strings, optional)*: Work-in-progress photo URLs.
- **Request Example**:
```json
{
  "progress_percentage": 75,
  "description": "Replaced damaged pipe section. Currently testing water pressure.",
  "photos": [
    "https://storage.example.com/progress/photo2.jpg"
  ]
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Progress updated.",
  "data": {
    "id": 5,
    "service_request_id": 12,
    "technician_id": 15,
    "progress_percentage": 75,
    "description": "Replaced damaged pipe section. Currently testing water pressure.",
    "photos": [
      "https://storage.example.com/progress/photo2.jpg"
    ],
    "created_at": "2026-09-05T00:08:45.000000Z"
  }
}
```

---

### POST `/api/v1/technician/jobs/{assignmentId}/complete`
- **Scenario**: Technician completes job execution on-site and marks assignment as completed. The final invoice is issued at this point so the customer can pay the remaining balance in the app. `GET /technician/jobs/{assignmentId}/invoice` now also returns `deposit_paid`, `balance_due`, `is_paid` and `payment_status` (`awaiting_customer_payment` | `paid`); technicians do not collect payment.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload**: *None*
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Job marked as completed.",
  "data": {
    "id": 8,
    "service_request_id": 12,
    "status": "completed",
    "completed_at": "2026-09-05T00:09:00.000000Z"
  }
}
```
