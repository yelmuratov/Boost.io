<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BonusUnlockedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public float $amount
    ) {
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('🎊 Your Bonus is Now Unlocked!')
            ->view('emails.bonus-unlocked')
            ->with([
                'userName' => $this->user->user_name,
                'amount' => number_format((float) $this->amount, 2),
                'newBalance' => number_format((float) $this->user->balance, 2),
            ]);
    }
}
