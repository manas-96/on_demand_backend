<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatNotificationController extends Controller
{
    public function __construct(
        protected PushNotificationService $notificationService
    ) {}

    /**
     * Send push notification for a Firestore chat message.
     */
    public function notify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'string'],
            'receiver_id' => ['required'],
            'message' => ['required', 'string'],
        ]);

        $sender = $request->user();
        $senderId = (string) $sender->id;
        $receiverId = (string) $validated['receiver_id'];
        $jobId = trim($validated['job_id']);

        // Avoid notification when current user is the sender (Section 12)
        if ($senderId === $receiverId) {
            return response()->json([
                'success' => true,
                'message' => 'Sender and receiver are the same user. Notification skipped.',
            ]);
        }

        $notificationData = [
            'type' => NotificationType::Message->value,
            'booking_id' => $jobId,
            'job_id' => $jobId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'screen' => 'chat',
        ];

        $notification = $this->notificationService->send(
            userId: $receiverId,
            title: 'New Message',
            body: $validated['message'],
            type: NotificationType::Message->value,
            data: $notificationData
        );

        return response()->json([
            'success' => true,
            'message' => 'Chat message push notification sent successfully.',
            'data' => $notification,
        ]);
    }
}
