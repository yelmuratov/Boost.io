<?php

namespace App\Mail;

use App\Models\User;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BonusAwardedMail extends Mailable
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
        $unlockThreshold = SystemSetting::get('bonus.unlock_threshold', 10000);

        return $this->subject('🎉 Welcome Bonus Awarded!')
            ->view('emails.bonus-awarded')
            ->with([
                'userName' => $this->user->user_name,
                'amount' => number_format((float) $this->amount, 2),
                'unlockThreshold' => number_format((float) $unlockThreshold, 2),
                'currentBalance' => number_format((float) $this->user->balance, 2),
            ]);
    }
}
