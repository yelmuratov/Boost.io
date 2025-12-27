<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {
    }

    /**
     * Get wallet summary
     */
    public function index(Request $request)
    {
        $summary = $this->walletService->getWalletSummary($request->user());

        return response()->json([
            'success' => true,
            'wallet' => $summary,
        ]);
    }

    /**
     * Get transaction history
     */
    public function transactions(Request $request)
    {
        $filters = $request->only(['type', 'from_date', 'to_date', 'per_page']);
        $transactions = $this->walletService->getUserTransactions($request->user(), $filters);

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
        ]);
    }
}
