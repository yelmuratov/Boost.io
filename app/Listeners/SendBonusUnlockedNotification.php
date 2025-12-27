<?php

namespace App\Listeners;

use App\Events\BonusUnlocked;
use App\Mail\BonusUnlockedMail;
use Illuminate\Support\Facades\Mail;

class SendBonusUnlockedNotification
{
    /**
     * Handle the event.
     */
    public function handle(BonusUnlocked $event): void
    {
        Mail::to($event->user->email)->send(
            new BonusUnlockedMail($event->user, $event->amount)
        );
    }
}
