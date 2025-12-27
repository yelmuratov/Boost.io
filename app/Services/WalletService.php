<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class WalletService
{
    /**
     * Add balance to user's main wallet
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @param array $metadata
     * @return Transaction
     */
    public function addBalance(User $user, float $amount, string $description, array $metadata = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $metadata) {
            $user->refresh();
            $balanceBefore = $user->balance;

            $user->increment('balance', $amount);
            $user->refresh();

            return $this->logTransaction($user, 'credit', $amount, [
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Deduct balance from user's main wallet
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @param mixed $reference
     * @return Transaction
     * @throws \Exception
     */
    public function deductBalance(User $user, float $amount, string $description, $reference = null): Transaction
    {
        if (!$user->canAfford($amount)) {
            throw new \Exception('Insufficient balance');
        }

        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            $user->refresh();
            $balanceBefore = $user->balance;

            $user->decrement('balance', $amount);
            $user->refresh();

            $transactionData = [
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => $description,
            ];

            if ($reference) {
                $transactionData['reference_type'] = get_class($reference);
                $transactionData['reference_id'] = $reference->id;
            }

            return $this->logTransaction($user, 'debit', $amount, $transactionData);
        });
    }

    /**
     * Admin manual balance adjustment
     *
     * @param User $user
     * @param float $amount Positive or negative
     * @param string $reason
     * @return Transaction
     */
    public function adminAdjustBalance(User $user, float $amount, string $reason): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $user->refresh();
            $balanceBefore = $user->balance;

            if ($amount >= 0) {
                $user->increment('balance', abs($amount));
            } else {
                $user->decrement('balance', abs($amount));
            }

            $user->refresh();

            return $this->logTransaction($user, 'admin_adjustment', $amount, [
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => "Admin adjustment: {$reason}",
            ]);
        });
    }

    /**
     * Log a transaction
     *
     * @param User $user
     * @param string $type
     * @param float $amount
     * @param array $options
     * @return Transaction
     */
    public function logTransaction(User $user, string $type, float $amount, array $options = []): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $options['balance_before'] ?? $user->balance,
            'balance_after' => $options['balance_after'] ?? $user->balance,
            'description' => $options['description'] ?? '',
            'reference_type' => $options['reference_type'] ?? null,
            'reference_id' => $options['reference_id'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    /**
     * Get user transaction history
     *
     * @param User $user
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserTransactions(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->transactions()->orderBy('created_at', 'desc');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get wallet summary for user
     *
     * @param User $user
     * @return array
     */
    public function getWalletSummary(User $user): array
    {
        $user->refresh();

        $unlockProgress = 0;
        if (!$user->bonus_unlocked && $user->bonus_balance > 0) {
            $threshold = \App\Models\SystemSetting::get('bonus.unlock_threshold', 10000);
            $unlockProgress = min(100, ($user->total_spent / $threshold) * 100);
        }

        return [
            'balance' => (float) $user->balance,
            'bonus_balance' => (float) $user->bonus_balance,
            'total_spent' => (float) $user->total_spent,
            'bonus_unlocked' => $user->bonus_unlocked,
            'bonus_unlocked_at' => $user->bonus_unlocked_at?->toISOString(),
            'total_available' => $user->getTotalBalance(),
            'unlock_progress_percentage' => round($unlockProgress, 2),
        ];
    }
}
