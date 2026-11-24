<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    /**
     * Send push notification to a single user.
     */
    public function send(
        int|string $userId,
        string $title,
        string $body,
        string $type,
        array $data = [],
        ?string $eventId = null
    ): ?Notification {
        $user = User::find($userId);
        if (! $user) {
            Log::warning('PushNotificationService: User not found.', ['user_id' => $userId]);

            return null;
        }

        // Idempotency check to prevent duplicate notifications
        if ($eventId !== null) {
            $existing = Notification::where('user_id', $user->id)
                ->where('type', $type)
                ->where('created_at', '>=', now()->subHours(24))
                ->get()
                ->first(function ($item) use ($eventId) {
                    $itemData = $item->data ?? [];

                    return isset($itemData['event_id']) && $itemData['event_id'] === $eventId;
                });

            if ($existing) {
                Log::info('PushNotificationService: Skipping duplicate notification.', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'event_id' => $eventId,
                ]);

                return $existing;
            }

            $data['event_id'] = $eventId;
        }

        // Store notification history record
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sent_at' => now(),
        ]);

        // Get active device tokens for the user
        $tokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($tokens->isEmpty()) {
            Log::info('PushNotificationService: No active FCM tokens for user.', ['user_id' => $user->id]);

            return $notification;
        }

        // Send FCM notification to all active tokens
        foreach ($tokens as $deviceToken) {
            $this->sendFcmMessage($deviceToken, $title, $body, $data);
        }

        return $notification;
    }

    /**
     * Send push notification to multiple users.
     */
    public function sendToUsers(
        array $userIds,
        string $title,
        string $body,
        string $type,
        array $data = [],
        ?string $eventId = null
    ): array {
        $results = [];

        foreach ($userIds as $userId) {
            $results[] = $this->send($userId, $title, $body, $type, $data, $eventId);
        }

        return array_filter($results);
    }

    /**
     * Dispatch FCM HTTP v1 message.
     */
    protected function sendFcmMessage(DeviceToken $deviceToken, string $title, string $body, array $data): bool
    {
        try {
            $projectId = config('services.firebase.project_id');
            if (empty($projectId)) {
                Log::warning('PushNotificationService: FIREBASE_PROJECT_ID not configured.');

                return false;
            }

            $accessToken = $this->getFcmAccessToken();
            if (! $accessToken) {
                Log::warning('PushNotificationService: Could not retrieve FCM OAuth2 access token.');

                return false;
            }

            // Ensure all data values are strings for FCM compatibility
            $stringData = [];
            foreach ($data as $key => $val) {
                $stringData[(string) $key] = is_array($val) ? json_encode($val) : (string) $val;
            }

            $url = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $projectId);

            $payload = [
                'message' => [
                    'token' => $deviceToken->device_token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
                    'android' => [
                        'priority' => 'HIGH',
                        'notification' => [
                            'sound' => 'default',
                        ],
                    ],
                    'apns' => [
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ],
            ];

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $deviceToken->update(['last_used_at' => now()]);

                return true;
            }

            // Handle invalid/expired tokens
            $statusCode = $response->status();
            $responseBody = $response->json();
            $errorCode = $responseBody['error']['details'][0]['errorCode'] ?? $responseBody['error']['status'] ?? '';

            if (in_array($statusCode, [401, 404, 410]) || in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT', 'THIRD_PARTY_AUTH_ERROR'])) {
                Log::info('PushNotificationService: Deactivating invalid FCM token.', [
                    'device_token_id' => $deviceToken->id,
                    'user_id' => $deviceToken->user_id,
                    'error' => $errorCode,
                ]);

                $deviceToken->update(['is_active' => false]);
            } else {
                Log::error('PushNotificationService: FCM request failed.', [
                    'status' => $statusCode,
                    'body' => $responseBody,
                    'user_id' => $deviceToken->user_id,
                ]);
            }

            return false;
        } catch (Throwable $e) {
            Log::error('PushNotificationService: Error sending FCM notification.', [
                'error' => $e->getMessage(),
                'user_id' => $deviceToken->user_id,
            ]);

            return false;
        }
    }

    /**
     * Retrieve Google Service Account OAuth2 Access Token for FCM HTTP v1.
     */
    protected function getFcmAccessToken(): ?string
    {
        $cachedToken = Cache::get('fcm_oauth2_access_token');
        if ($cachedToken !== null && is_string($cachedToken)) {
            return $cachedToken;
        }

        $credentialsPath = config('services.firebase.credentials');

        if ($credentialsPath && ! str_starts_with($credentialsPath, '/') && ! str_starts_with($credentialsPath, '\\') && ! preg_match('#^[a-zA-Z]:#', $credentialsPath)) {
            $credentialsPath = base_path($credentialsPath);
        }

        if (! $credentialsPath || ! file_exists($credentialsPath)) {
            Log::warning('PushNotificationService: Service account credentials file missing.', ['path' => $credentialsPath]);

            return null;
        }

        try {
            $json = json_decode(file_get_contents($credentialsPath), true);
            if (! is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
                Log::warning('PushNotificationService: Invalid service account credentials JSON structure.');

                return null;
            }

            $now = time();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claim = [
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging https://www.googleapis.com/auth/datastore',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ];

            $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
            $base64UrlClaim = $this->base64UrlEncode(json_encode($claim));
            $signatureInput = $base64UrlHeader.'.'.$base64UrlClaim;

            $signature = '';
            $success = openssl_sign($signatureInput, $signature, $json['private_key'], OPENSSL_ALGO_SHA256);
            if (! $success) {
                Log::error('PushNotificationService: openssl_sign failed for Firebase JWT.');

                return null;
            }

            $jwt = $signatureInput.'.'.$this->base64UrlEncode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $token = $response->json('access_token');
                if (! empty($token)) {
                    Cache::put('fcm_oauth2_access_token', $token, 3300);

                    return $token;
                }
            }

            Log::error('PushNotificationService: OAuth2 token exchange failed.', ['body' => $response->body()]);

            return null;
        } catch (Throwable $e) {
            Log::error('PushNotificationService: Failed to generate FCM access token.', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Write chat message directly to Cloud Firestore collection 'messages'.
     */
    public function sendFirestoreMessage(
        string $jobId,
        int|string $senderId,
        int|string $receiverId,
        string $message
    ): bool {
        $token = $this->getFcmAccessToken();
        if (! $token) {
            Log::warning('PushNotificationService: Unable to get access token for Firestore write.');

            return false;
        }

        $projectId = config('services.firebase.project_id', 'on-demand-3834d');
        $url = sprintf('https://firestore.googleapis.com/v1/projects/%s/databases/(default)/documents/messages', $projectId);

        $nowIso = now()->toIso8601ZuluString();

        $payload = [
            'fields' => [
                'job_id' => ['stringValue' => (string) $jobId],
                'message' => ['stringValue' => (string) $message],
                'sender_id' => is_numeric($senderId) ? ['integerValue' => (int) $senderId] : ['stringValue' => (string) $senderId],
                'receiver_id' => is_numeric($receiverId) ? ['integerValue' => (int) $receiverId] : ['stringValue' => (string) $receiverId],
                'created_at' => ['timestampValue' => $nowIso],
                'delivered_at' => ['stringValue' => $nowIso],
                'seen_at' => ['nullValue' => null],
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            // If token was expired or scope was stale, clear cache and retry once with a fresh token
            if ($response->status() === 401) {
                Log::info('PushNotificationService: Firestore returned 401, refreshing cached access token.');
                Cache::forget('fcm_oauth2_access_token');
                $freshToken = $this->getFcmAccessToken();
                if ($freshToken) {
                    $retryResponse = Http::withToken($freshToken)
                        ->acceptJson()
                        ->post($url, $payload);

                    if ($retryResponse->successful()) {
                        return true;
                    }
                }
            }

            Log::error('PushNotificationService: Firestore message creation failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('PushNotificationService: Firestore message exception: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Helper for Base64URL encoding.
     */
    protected function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
