<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtpNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $otp) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name') && config('app.name') !== 'Laravel'
            ? config('app.name')
            : 'Grab A Hand';
        $fromName = config('mail.from.name') && config('mail.from.name') !== 'Laravel'
            ? config('mail.from.name')
            : $appName;
        $fromAddress = config('mail.from.address') ?: 'info@ycrainxdevlabs.com';

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject('Grab A Hand - Password Reset Code')
            ->greeting('Hello!')
            ->line('We received a request to reset your password.')
            ->line('Your 6-digit password reset code is:')
            ->line('**'.$this->otp.'**')
            ->line('This code will expire in 10 minutes.')
            ->line('If you did not request a password reset, please ignore this email or contact support if you have concerns.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
