<?php

namespace App\Mail;

use App\Models\JobAssignment;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public JobAssignment $assignment,
        public User $recipient,
        public string $recipientRole // 'customer', 'provider', 'technician'
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
            subject: sprintf('Grab A Hand - Booking Completed & Settled: #%s', $this->serviceRequest->request_number),
        );
    }

    public function content(): Content
    {
        $subject = sprintf('Grab A Hand - Booking Completed & Settled: #%s', $this->serviceRequest->request_number);

        return new Content(
            view: 'emails.booking_completed',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'assignment' => $this->assignment,
                'recipient' => $this->recipient,
                'recipientRole' => $this->recipientRole,
                'subject' => $subject,
            ],
        );
    }
}
