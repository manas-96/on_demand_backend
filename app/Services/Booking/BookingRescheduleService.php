<?php

namespace App\Services\Booking;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Services\EmailNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingRescheduleService
{
    public function __construct(protected EmailNotificationService $emailNotificationService) {}

    /**
     * Move a booking to a new date and time, then email its provider and technician.
     *
     * @param  array{preferred_date: string, preferred_start_time?: ?string, preferred_end_time?: ?string}  $schedule
     *
     * @throws ValidationException when the booking can no longer be moved or the new slot is not valid
     */
    public function reschedule(ServiceRequest $serviceRequest, array $schedule): ServiceRequest
    {
        if (in_array($serviceRequest->status, [ServiceRequestStatus::InProgress, ServiceRequestStatus::Completed, ServiceRequestStatus::Cancelled], true)) {
            throw ValidationException::withMessages([
                'preferred_date' => 'This booking can no longer be rescheduled.',
            ]);
        }

        if (! $serviceRequest->canBeRescheduled()) {
            throw ValidationException::withMessages([
                'preferred_date' => sprintf(
                    'Bookings can only be rescheduled up to %d hours before the scheduled time.',
                    ServiceRequest::RESCHEDULE_CUTOFF_HOURS,
                ),
            ]);
        }

        $newStartTime = $schedule['preferred_start_time'] ?? null;
        $newEndTime = $schedule['preferred_end_time'] ?? null;
        $newStart = Carbon::parse(
            $schedule['preferred_date'].' '.($newStartTime ?? '00:00'),
            $serviceRequest->localTimezone(),
        );

        if ($newStart->lte(now())) {
            throw ValidationException::withMessages([
                'preferred_start_time' => 'Please choose a date and time in the future.',
            ]);
        }

        if ($this->isSameSlot($serviceRequest, $schedule['preferred_date'], $newStartTime)) {
            throw ValidationException::withMessages([
                'preferred_date' => 'Your booking is already scheduled for that date and time.',
            ]);
        }

        $previousSchedule = $serviceRequest->scheduleLabel();

        $serviceRequest->update([
            'preferred_date' => $schedule['preferred_date'],
            'preferred_start_time' => $newStartTime,
            'preferred_end_time' => $newEndTime,
        ]);

        $serviceRequest->refresh();

        $this->emailNotificationService->sendBookingRescheduled($serviceRequest, $previousSchedule);

        return $serviceRequest;
    }

    private function isSameSlot(ServiceRequest $serviceRequest, string $date, ?string $startTime): bool
    {
        $currentTime = $serviceRequest->preferred_start_time
            ? Carbon::parse($serviceRequest->preferred_start_time)->format('H:i')
            : null;

        return $serviceRequest->preferred_date?->toDateString() === Carbon::parse($date)->toDateString()
            && $currentTime === ($startTime ? Carbon::parse($startTime)->format('H:i') : null);
    }
}
