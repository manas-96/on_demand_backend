<?php

namespace App\Mail;

use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProviderRegisteredAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProviderProfile $providerProfile,
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        $appName = config('app.name') && config('app.name') !== 'Laravel' ? config('app.name') : 'Grab A Hand';
        $fromName = config('mail.from.name') && config('mail.from.name') !== 'Laravel' ? config('mail.from.name') : $appName;
        $fromAddress = config('mail.from.address') ?: 'info@ycrainxdevlabs.com';

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.provider_registered_admin',
            with: [
                'providerProfile' => $this->providerProfile,
                'recipient' => $this->recipient,
                'subject' => $this->subjectLine(),
            ],
        );
    }

    private function subjectLine(): string
    {
        return sprintf('Grab A Hand - New provider awaiting approval: %s', $this->providerProfile->company_name);
    }
}
