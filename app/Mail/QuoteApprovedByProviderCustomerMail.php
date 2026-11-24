<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteApprovedByProviderCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ServiceRequest $serviceRequest,
        public mixed $quote
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
            subject: sprintf('Grab A Hand - Quotation Ready for Review: #%s', $this->serviceRequest->request_number),
        );
    }

    public function content(): Content
    {
        $subject = sprintf('Grab A Hand - Quotation Ready for Review: #%s', $this->serviceRequest->request_number);

        return new Content(
            view: 'emails.quote_approved_provider_customer',
            with: [
                'serviceRequest' => $this->serviceRequest,
                'quote' => $this->quote,
                'subject' => $subject,
            ],
        );
    }
}
