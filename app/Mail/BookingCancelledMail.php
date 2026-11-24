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

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $recipient,
        public string $recipientRole, // 'customer', 'provider', 'technician'
        public string $cancelledBy = 'customer', // 'customer', 'provider', 'admin'
        public ?string $reason = null
    ) {}

    /**
     * Get the message envelope.
     */
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
            subject: sprintf(
                'Grab A Hand - Booking Request Cancelled: #%s (%s)',
                $this->serviceRequest->request_number,
                $this->serviceRequest->category?->name ?? 'Service Request'
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $subject = sprintf(
            'Grab A Hand - Booking Request Cancelled: #%s (%s)',
            $this->serviceRequest->request_number,
            $this->serviceRequest->category?->name ?? 'Service Request'
        );

        return new Content(
            view: 'emails.booking_cancelled',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'recipient' => $this->recipient,
                'recipientRole' => $this->recipientRole,
                'cancelledBy' => $this->cancelledBy,
                'reason' => $this->reason,
                'subject' => $subject,
            ],
        );
    }
}
