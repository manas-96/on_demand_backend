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
   - [POST /api/v1/customer/invoices/{invoiceId}/pay](#post-apiv1customerinvoicesinvoiceidpay)
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
  "message": "Service request created successfully.",
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

### POST `/api/v1/customer/invoices/{invoiceId}/pay`
- **Scenario**: Customer pays an issued job invoice via payment gateway. Automatically updates invoice status to `'paid'`, service request status to `'completed'`, and records provider earnings.
- **Headers**: `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Payload Schema**:
  - `transaction_id` *(string, required)*: Payment gateway transaction reference string.
  - `amount` *(number, required, min: 1)*: Paid amount (must match or exceed invoice total).
  - `payment_gateway` *(string, optional, default: 'stripe')*: Payment gateway identifier.
- **Request Example**:
```json
{
  "transaction_id": "txn_3Mq1234567890",
  "amount": 131.25,
  "payment_gateway": "stripe"
}
```
- **Success Response (`200 OK`)**:
```json
{
  "success": true,
  "message": "Payment processed successfully.",
  "data": {
    "id": 7,
    "invoice_id": 2,
    "customer_id": 5,
    "payment_gateway": "stripe",
    "transaction_id": "txn_3Mq1234567890",
    "amount": "131.25",
    "status": "successful",
    "paid_at": "2026-09-05T00:05:00.000000Z",
    "created_at": "2026-09-05T00:05:00.000000Z"
  }
}
```

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
- **Scenario**: Technician completes job execution on-site and marks assignment as completed.
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
