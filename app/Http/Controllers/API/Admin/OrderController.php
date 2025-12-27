<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmmOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Get all orders (admin view)
     */
    public function index(Request $request)
    {
        $query = SmmOrder::with(['user', 'service', 'provider'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * Get single order (admin view)
     */
    public function show(int $id)
    {
        $order = SmmOrder::with(['user', 'service', 'provider', 'transaction'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Get order statistics
     */
    public function stats()
    {
        $stats = [
            'total_orders' => SmmOrder::count(),
            'pending_orders' => SmmOrder::where('status', 'pending')->count(),
            'completed_orders' => SmmOrder::where('status', 'completed')->count(),
            'total_revenue' => SmmOrder::sum('charge'),
            'total_cost' => SmmOrder::sum('cost'),
            'total_profit' => SmmOrder::sum('charge') - SmmOrder::sum('cost'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
