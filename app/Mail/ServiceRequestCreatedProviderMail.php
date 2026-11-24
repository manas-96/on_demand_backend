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

class ServiceRequestCreatedProviderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public User $provider
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
            subject: sprintf('Grab A Hand - New Service Request Available: #%s (%s)', $this->serviceRequest->request_number, $this->serviceRequest->category?->name ?? 'Service'),
        );
    }

    public function content(): Content
    {
        $subject = sprintf('Grab A Hand - New Service Request Available: #%s (%s)', $this->serviceRequest->request_number, $this->serviceRequest->category?->name ?? 'Service');

        return new Content(
            view: 'emails.service_request_created_provider',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'provider' => $this->provider,
                'subject' => $subject,
            ],
        );
    }
}
