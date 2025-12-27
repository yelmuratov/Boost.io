<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    /**
     * Create a new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:smm_services,id',
            'link' => 'required|string|url',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->orderService->createOrder($request->user(), $validated);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order->load(['service', 'provider']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get user's orders
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'from_date', 'to_date', 'per_page']);
        $orders = $this->orderService->getUserOrders($request->user(), $filters);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get single order details
     */
    public function show(Request $request, int $id)
    {
        try {
            $order = $this->orderService->getOrderDetails($request->user(), $id);

            return response()->json([
                'success' => true,
                'order' => $order,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }
    }
}
