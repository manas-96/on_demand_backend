<?php

namespace App\Jobs;

use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int|string $userId,
        public string $title,
        public string $body,
        public string $type,
        public array $data = [],
        public ?string $eventId = null
    ) {}

    public function handle(PushNotificationService $notificationService): void
    {
        $notificationService->send(
            userId: $this->userId,
            title: $this->title,
            body: $this->body,
            type: $this->type,
            data: $this->data,
            eventId: $this->eventId
        );
    }
}
