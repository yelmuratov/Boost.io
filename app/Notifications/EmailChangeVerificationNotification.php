<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class EmailChangeVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $verificationUrl,
        private string $newEmail
    ) {
    }

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
        return (new MailMessage)
            ->subject('Verify Your Email Change - Boostio.uz')
            ->greeting('Email Change Request')
            ->line('You requested to change your email address to: ' . $this->newEmail)
            ->line('Please click the button below to verify your new email address.')
            ->action('Verify New Email Address', $this->verificationUrl)
            ->line('This verification link will expire in 60 minutes.')
            ->line('If you did not request this change, please ignore this email and your email address will remain unchanged.')
            ->salutation('Best regards, The Boostio.uz Team');
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
