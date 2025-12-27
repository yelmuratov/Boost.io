<?php

namespace App\Services;

use App\Models\User;
use App\Models\SystemSetting;
use App\Events\BonusAwarded;
use App\Events\BonusUnlocked;
use Illuminate\Support\Facades\DB;

class BonusService
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * Award welcome bonus to user on email verification
     *
     * @param User $user
     * @return bool
     */
    public function awardWelcomeBonus(User $user): bool
    {
        // Check if bonus system is enabled
        if (!SystemSetting::get('bonus.enabled', true)) {
            return false;
        }

        // Check if user already received welcome bonus using flag
        // This is faster and more reliable than checking transactions
        if ($user->bonus_awarded) {
            return false;
        }

        $bonusAmount = SystemSetting::get('bonus.registration_amount', 5000.00);

        return DB::transaction(function () use ($user, $bonusAmount) {
            $user->refresh();
            $balanceBefore = $user->bonus_balance;

            // Award bonus and set the flag
            $user->update([
                'bonus_balance' => $bonusAmount,
                'bonus_awarded' => true,  // Mark that user received bonus
            ]);
            $user->refresh();

            // Log transaction
            $this->walletService->logTransaction($user, 'bonus_award', $bonusAmount, [
                'balance_before' => $balanceBefore,
                'balance_after' => $user->bonus_balance,
                'description' => 'Welcome bonus awarded on email verification',
            ]);

            // Fire event for email notification
            event(new BonusAwarded($user, $bonusAmount));

            return true;
        });
    }

    /**
     * Check if user has reached unlock threshold and unlock bonus if so
     *
     * @param User $user
     * @return bool
     */
    public function checkAndUnlockBonus(User $user): bool
    {
        $user->refresh();

        // Check if bonus is already unlocked or there's no bonus
        if ($user->bonus_unlocked || $user->bonus_balance <= 0) {
            return false;
        }

        $unlockThreshold = SystemSetting::get('bonus.unlock_threshold', 10000.00);

        // Check if user has spent enough
        if ($user->total_spent < $unlockThreshold) {
            return false;
        }

        return DB::transaction(function () use ($user) {
            $bonusAmount = $user->bonus_balance;
            $balanceBefore = $user->balance;

            // Move bonus to main balance
            $user->increment('balance', $bonusAmount);
            $user->update([
                'bonus_balance' => 0,
                'bonus_unlocked' => true,
                'bonus_unlocked_at' => now(),
            ]);
            $user->refresh();

            // Log transaction
            $this->walletService->logTransaction($user, 'bonus_unlock', $bonusAmount, [
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => 'Bonus unlocked and added to main balance',
            ]);

            // Fire event for email notification
            event(new BonusUnlocked($user, $bonusAmount));

            return true;
        });
    }

    /**
     * Get current bonus configuration
     *
     * @return array
     */
    public function getBonusConfig(): array
    {
        return [
            'enabled' => SystemSetting::get('bonus.enabled', true),
            'registration_amount' => SystemSetting::get('bonus.registration_amount', 5000.00),
            'unlock_threshold' => SystemSetting::get('bonus.unlock_threshold', 10000.00),
        ];
    }

    /**
     * Update bonus configuration (admin only)
     *
     * @param array $settings
     * @return bool
     */
    public function updateBonusConfig(array $settings): bool
    {
        if (isset($settings['enabled'])) {
            SystemSetting::set('bonus.enabled', (bool) $settings['enabled']);
        }

        if (isset($settings['registration_amount'])) {
            SystemSetting::set('bonus.registration_amount', (float) $settings['registration_amount']);
        }

        if (isset($settings['unlock_threshold'])) {
            SystemSetting::set('bonus.unlock_threshold', (float) $settings['unlock_threshold']);
        }

        return true;
    }
}
