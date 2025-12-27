<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * Get user wallet summary
     */
    public function show(int $userId)
    {
        $user = User::findOrFail($userId);
        $summary = $this->walletService->getWalletSummary($user);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'user_name' => $user->user_name,
                'email' => $user->email,
            ],
            'wallet' => $summary,
        ]);
    }

    /**
     * Get user transaction history
     */
    public function transactions(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);
        $filters = $request->only(['type', 'from_date', 'to_date', 'per_page']);
        $transactions = $this->walletService->getUserTransactions($user, $filters);

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Manually adjust user balance
     */
    public function adjust(Request $request, int $userId)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($userId);

        try {
            $transaction = $this->walletService->adminAdjustBalance(
                $user,
                $validated['amount'],
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Balance adjusted successfully',
                'transaction' => $transaction,
                'new_balance' => $user->fresh()->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
