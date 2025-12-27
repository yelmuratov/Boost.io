<?php

namespace App\Services;

use App\Models\User;
use App\Models\SmmService;
use App\Models\SmmOrder;
use App\Services\SmmPanel\SmmService as SmmPanelService;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderService
{
    public function __construct(
        private WalletService $walletService,
        private BonusService $bonusService
    ) {
    }

    /**
     * Create a new SMM order
     *
     * @param User $user
     * @param array $orderData
     * @return SmmOrder
     * @throws \Exception
     */
    public function createOrder(User $user, array $orderData): SmmOrder
    {
        // 1. Validate service exists and is active
        $service = SmmService::with('provider')
            ->where('id', $orderData['service_id'])
            ->where('is_active', true)
            ->whereHas('provider', function ($query) {
                $query->where('is_active', true);
            })
            ->firstOrFail();

        // 2. Calculate charge based on service rate and quantity
        $quantity = $orderData['quantity'];
        $charge = ($service->rate / 1000) * $quantity;

        // 3. Check user balance
        if (!$user->canAfford($charge)) {
            throw new \Exception('Insufficient balance. Please add funds to your wallet.');
        }

        // 4. Begin transaction and create order
        return DB::transaction(function () use ($user, $service, $orderData, $charge, $quantity) {
            // Deduct balance
            $transaction = $this->walletService->deductBalance(
                $user,
                $charge,
                "Order for {$service->name}"
            );

            // Submit order to SMM provider
            $provider = $service->provider;
            $smmService = new SmmPanelService($provider->api_url, $provider->api_key);

            try {
                $response = $smmService->createOrder([
                    'service' => $service->service_id,
                    'link' => $orderData['link'],
                    'quantity' => $quantity,
                ]);

                // Create order record
                $order = SmmOrder::create([
                    'user_id' => $user->id,
                    'provider_id' => $provider->id,
                    'service_id' => $service->id,
                    'order_id' => $response->order ?? null,
                    'link' => $orderData['link'],
                    'quantity' => $quantity,
                    'charge' => $charge,
                    'cost' => ($service->cost / 1000) * $quantity, // What we pay the provider
                    'status' => 'pending',
                    'order_data' => $orderData,
                    'response_data' => (array) $response,
                ]);

                // Link transaction to order
                $transaction->update([
                    'reference_type' => SmmOrder::class,
                    'reference_id' => $order->id,
                ]);

                // Update user's total_spent (based on our markup, the charge)
                $user->increment('total_spent', $charge);
                $user->refresh();

                // Check if bonus should be unlocked
                $this->bonusService->checkAndUnlockBonus($user);

                return $order->fresh();

            } catch (\Exception $e) {
                // If provider order fails, refund the user
                $this->walletService->addBalance(
                    $user,
                    $charge,
                    "Refund for failed order: {$e->getMessage()}"
                );

                throw new \Exception("Failed to create order with provider: " . $e->getMessage());
            }
        });
    }

    /**
     * Get user's orders
     *
     * @param User $user
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserOrders(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = $user->orders()
            ->with(['service', 'provider'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
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
     * Get order details
     *
     * @param User $user
     * @param int $orderId
     * @return SmmOrder
     * @throws \Exception
     */
    public function getOrderDetails(User $user, int $orderId): SmmOrder
    {
        $order = SmmOrder::with(['service', 'provider', 'transaction'])
            ->where('user_id', $user->id)
            ->findOrFail($orderId);

        return $order;
    }
}
