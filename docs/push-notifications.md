# Push Notification System — Technical Documentation

This document describes the design, configuration, API contracts, backend usage, and Flutter client integration guidelines for the On-Demand Service Push Notification System.

---

## 1. Firebase Cloud Messaging (FCM) Credentials & Setup

The backend uses Firebase Cloud Messaging (FCM) HTTP v1 API with Google OAuth2 JWT authentication.

### Environment Variables (`.env`)

```env
FIREBASE_PROJECT_ID=on-demand-3834d
FIREBASE_CREDENTIALS=storage/app/firebase-service-account.json
FIREBASE_API_KEY=AIzaSyDJHyKxNOWHwe...
```

### Configuration (`config/services.php`)

```php
'firebase' => [
    'api_key' => env('FIREBASE_API_KEY'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-service-account.json')),
],
```

> [!IMPORTANT]
> Never commit `storage/app/firebase-service-account.json` to public Git repositories. Store the file securely on production servers.

---

## 2. Notification Types & Enums

Notification types are defined in `App\Enums\NotificationType`:

```php
namespace App\Enums;

enum NotificationType: string
{
    case Booking = 'booking';
    case Message = 'message';
    case Promotional = 'promotional';
}
```

---

## 3. Backend Usage

Use `App\Services\PushNotificationService` across controllers, services, jobs, or listeners.

### Send to a Single User

```php
use App\Services\PushNotificationService;
use App\Enums\NotificationType;

app(PushNotificationService::class)->send(
    userId: $customerId,
    title: 'Job Accepted',
    body: 'Your service request has been accepted by the provider.',
    type: NotificationType::Booking->value,
    data: [
        'type' => 'booking',
        'booking_id' => $serviceRequest->request_number,
        'screen' => 'booking_details',
    ],
    eventId: $serviceRequest->request_number . '_accepted_' . $customerId
);
```

### Send to Multiple Users (Broadcast)

```php
app(PushNotificationService::class)->sendToUsers(
    userIds: [12, 15, 20],
    title: 'Special Weekend Offer',
    body: 'Get 20% off your next AC service!',
    type: NotificationType::Promotional->value,
    data: [
        'type' => 'promotional',
        'screen' => 'home',
    ]
);
```

### Asynchronous Queueable Dispatch

```php
use App\Jobs\SendPushNotificationJob;

SendPushNotificationJob::dispatch(
    userId: $customerId,
    title: 'Job Accepted',
    body: 'Your service request has been accepted by the provider.',
    type: 'booking',
    data: [
        'type' => 'booking',
        'booking_id' => 'SR-20261003-000001',
        'screen' => 'booking_details',
    ]
);
```

---

## 4. API Endpoints

### 4.1 Device Token Registration

#### `POST /api/v1/device-token`
* **Auth**: Required (`auth:sanctum`)
* **Request Payload**:
  ```json
  {
      "device_token": "FCM_DEVICE_TOKEN_STRING",
      "platform": "android",
      "device_id": "device_unique_id_optional"
  }
  ```
* **Response**:
  ```json
  {
      "success": true,
      "message": "Device token registered successfully.",
      "data": {
          "id": 1,
          "user_id": 12,
          "device_token": "FCM_DEVICE_TOKEN_STRING",
          "platform": "android",
          "device_id": "device_unique_id_optional",
          "is_active": true,
          "last_used_at": "2026-10-03T00:40:00.000000Z"
      }
  }
  ```

#### `DELETE /api/v1/device-token`
* **Auth**: Required (`auth:sanctum`)
* **Request Payload**:
  ```json
  {
      "device_token": "FCM_DEVICE_TOKEN_STRING"
  }
  ```

---

### 4.2 In-App Notifications History

#### `GET /api/v1/notifications`
* **Auth**: Required (`auth:sanctum`)
* **Response**:
  ```json
  {
      "success": true,
      "message": "Notifications retrieved successfully.",
      "unread_count": 2,
      "data": [
          {
              "id": 5,
              "type": "booking",
              "title": "Job Accepted",
              "body": "Your service request has been accepted by the provider.",
              "data": {
                  "type": "booking",
                  "booking_id": "SR-20261003-000001",
                  "screen": "booking_details"
              },
              "read_at": null,
              "sent_at": "2026-10-03T00:40:00.000000Z",
              "created_at": "2026-10-03T00:40:00.000000Z"
          }
      ]
  }
  ```

#### `POST /api/v1/notifications/{id}/read`
* Mark single notification as read.

#### `POST /api/v1/notifications/read-all`
* Mark all user notifications as read.

---

### 4.3 Chat Push Notification Dispatcher

#### `POST /api/v1/chat/notify`
* **Auth**: Required (`auth:sanctum`)
* **Request Payload**:
  ```json
  {
      "job_id": "SR-20261003-000001",
      "receiver_id": 39,
      "message": "Hi, I am outside the building."
  }
  ```
* **Behavior**:
  * Authed user is treated as `sender_id`.
  * If `sender_id == receiver_id`, notification dispatch is safely skipped.
  * FCM Push payload sent to `receiver_id`.

---

### 4.4 Admin Promotional Notifications

#### `POST /api/v1/admin/notifications/promotional`
* **Auth**: Admin (`auth:sanctum`)
* **Request Payload**:
  ```json
  {
      "title": "Special Offer",
      "body": "Get 20% off your next service.",
      "customer_ids": [12, 15, 20]
  }
  ```
  *(Omit `customer_ids` or pass empty array to broadcast to ALL active customers)*.

---

## 5. Event $\rightarrow$ Recipient Mapping Matrix

| Event | Recipient | Type | Title | Body | Payload Data (`screen`) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| Provider accepts job | Customer | `booking` | Job Accepted | Your service request has been accepted by the provider. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Technician assigned | Customer | `booking` | Technician Assigned | A technician has been assigned to your service request. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Technician assigned | Technician | `booking` | New Job Assignment | You have been assigned to a new service request. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Quotation approved | Customer | `booking` | Quotation Approved | The quotation for your service request has been approved. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Quotation approved | Technician | `booking` | Quotation Approved | The quotation for the assigned job has been approved. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Job completed | Customer | `booking` | Job Completed | Your service request has been completed. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Job completed | Technician | `booking` | Job Completed | The assigned job has been marked as completed. | `{"type":"booking", "booking_id":"SR-...", "screen":"booking_details"}` |
| Provider sends message | Customer | `message` | New Message | *Message text* | `{"type":"message", "booking_id":"SR-...", "job_id":"SR-...", "sender_id":"...", "receiver_id":"...", "screen":"chat"}` |
| Admin promotional | Customer | `promotional` | *Custom Title* | *Custom Body* | `{"type":"promotional", "screen":"home"}` |

---

## 6. Flutter Application Integration Contract

The Flutter Customer & Technician applications must handle incoming FCM messages as follows:

```dart
void handleNotificationTap(Map<String, dynamic> data) {
  final String? type = data['type'];
  final String? screen = data['screen'];
  final String? bookingId = data['booking_id'] ?? data['job_id'];

  if (type == 'booking' && screen == 'booking_details' && bookingId != null) {
    Navigator.pushNamed(context, '/booking-details', arguments: {'booking_id': bookingId});
  } else if (type == 'message' && screen == 'chat' && bookingId != null) {
    Navigator.pushNamed(context, '/chat', arguments: {
      'job_id': bookingId,
      'sender_id': data['sender_id'],
      'receiver_id': data['receiver_id'],
    });
  } else if (type == 'promotional' || screen == 'home') {
    Navigator.pushNamed(context, '/home');
  }
}
```

---

## 7. Testing Instructions

Run the full automated test suite:

```bash
php artisan test --filter=PushNotificationSystemTest
```

Or run all backend unit & feature tests:

```bash
php artisan test --compact
```
