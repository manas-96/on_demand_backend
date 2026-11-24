<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function __construct(
        protected PushNotificationService $notificationService
    ) {}

    /**
     * API endpoint to send promotional notifications to selected or all customers.
     */
    public function sendPromotionalApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $recipientIds = $this->resolveCustomerIds($validated['customer_ids'] ?? null);

        if (empty($recipientIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No active customer recipients found.',
            ], 422);
        }

        $payload = [
            'type' => NotificationType::Promotional->value,
            'screen' => 'home',
        ];

        $notifications = $this->notificationService->sendToUsers(
            userIds: $recipientIds,
            title: $validated['title'],
            body: $validated['body'],
            type: NotificationType::Promotional->value,
            data: $payload
        );

        return response()->json([
            'success' => true,
            'message' => sprintf('Promotional notification dispatched to %d customer(s).', count($recipientIds)),
            'sent_count' => count($recipientIds),
        ]);
    }

    /**
     * Admin Panel Web View for sending promotional notifications.
     */
    public function index(Request $request): View
    {
        $customers = User::where('role', UserRole::Customer)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.notifications.index', compact('customers'));
    }

    /**
     * Handle promotional notification form submission from Admin Panel web UI.
     */
    public function sendPromotionalWeb(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $recipientIds = $this->resolveCustomerIds($validated['customer_ids'] ?? null);

        if (empty($recipientIds)) {
            return back()->with('error', 'No active customer recipients found.');
        }

        $payload = [
            'type' => NotificationType::Promotional->value,
            'screen' => 'home',
        ];

        $this->notificationService->sendToUsers(
            userIds: $recipientIds,
            title: $validated['title'],
            body: $validated['body'],
            type: NotificationType::Promotional->value,
            data: $payload
        );

        $activeTokensCount = DeviceToken::whereIn('user_id', $recipientIds)
            ->where('is_active', true)
            ->count();

        if ($activeTokensCount === 0) {
            return back()->with('success', sprintf(
                'In-app notification created for %d customer(s). Note: 0 active FCM device tokens were found in the database. Customer apps must register their device token via POST /api/v1/device-token to receive live FCM push popups.',
                count($recipientIds)
            ));
        }

        return back()->with('success', sprintf(
            'Promotional push notification sent successfully to %d customer(s) (%d active device token(s) reached)!',
            count($recipientIds),
            $activeTokensCount
        ));
    }

    /**
     * Resolve target customer IDs (all active customers if null/empty).
     */
    protected function resolveCustomerIds(?array $customerIds): array
    {
        if (! empty($customerIds)) {
            return User::whereIn('id', $customerIds)
                ->where('role', UserRole::Customer)
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();
        }

        return User::where('role', UserRole::Customer)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }
}
