<?php

namespace App\Listeners;

use App\Events\BonusAwarded;
use App\Mail\BonusAwardedMail;
use Illuminate\Support\Facades\Mail;

class SendBonusAwardedNotification
{
    /**
     * Handle the event.
     */
    public function handle(BonusAwarded $event): void
    {
        \Log::info('Sending bonus awarded email', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'amount' => $event->amount
        ]);

        Mail::to($event->user->email)->send(
            new BonusAwardedMail($event->user, $event->amount)
        );

        \Log::info('Bonus awarded email sent successfully');
    }
}
