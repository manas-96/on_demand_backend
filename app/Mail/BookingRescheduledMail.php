<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $recipientRole  'provider' or 'technician'
     * @param  string  $previousSchedule  The schedule before the change, already formatted for display
     */
    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public string $recipientRole,
        public string $previousSchedule,
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name') && config('app.name') !== 'Laravel'
            ? config('app.name')
            : 'Grab A Hand';
        $fromName = config('mail.from.name') && config('mail.from.name') !== 'Laravel'
            ? config('mail.from.name')
            : $appName;
        $fromAddress = config('mail.from.address') ?: 'info@ycrainxdevlabs.com';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking_rescheduled',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'recipient' => $this->recipient,
                'recipientRole' => $this->recipientRole,
                'previousSchedule' => $this->previousSchedule,
                'newSchedule' => $this->serviceRequest->scheduleLabel(),
                'subject' => $this->subjectLine(),
            ],
        );
    }

    private function subjectLine(): string
    {
        return sprintf('Grab A Hand - Booking Rescheduled: Request #%s', $this->serviceRequest->request_number);
    }
}
