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

class RequestAcceptedOrAssignedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public ?User $provider = null,
        public ?User $technician = null
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
            subject: sprintf('Grab A Hand - Service Request Accepted: #%s', $this->serviceRequest->request_number),
        );
    }

    public function content(): Content
    {
        $subject = sprintf('Grab A Hand - Service Request Accepted: #%s', $this->serviceRequest->request_number);

        return new Content(
            view: 'emails.request_accepted_assigned_customer',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'provider' => $this->provider,
                'technician' => $this->technician,
                'subject' => $subject,
            ],
        );
    }
}
