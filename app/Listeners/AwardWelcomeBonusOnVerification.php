<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use App\Services\BonusService;

class AwardWelcomeBonusOnVerification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private BonusService $bonusService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        $this->bonusService->awardWelcomeBonus($event->user);
    }
}
