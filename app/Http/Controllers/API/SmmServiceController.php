<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SmmService;
use App\Http\Resources\Smm\PublicSmmServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmmServiceController extends Controller
{
    /**
     * Get all active services (public access)
     * GET /api/services
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);

        $query = SmmService::query()
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_active', true);

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search in name/description/id/service_id
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('service_id', 'like', "%{$search}%");

                // If search is numeric, also search by ID
                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
                }
            });
        }

        // Filter by provider
        if ($request->has('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        // Price range filters
        if ($request->has('min_price')) {
            $query->where('rate', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('rate', '<=', $request->max_price);
        }

        // Sorting
        $query->orderBy('category')->orderBy('name');

        $services = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PublicSmmServiceResource::collection($services->items()),
            'meta' => [
                'current_page' => $services->currentPage(),
                'from' => $services->firstItem(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'to' => $services->lastItem(),
                'total' => $services->total(),
            ],
            'links' => [
                'first' => $services->url(1),
                'last' => $services->url($services->lastPage()),
                'prev' => $services->previousPageUrl(),
                'next' => $services->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get single service by ID
     * GET /api/services/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = SmmService::query()
                ->whereHas('provider', function ($q) {
                    $q->where('is_active', true);
                })
                ->where('is_active', true)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new PublicSmmServiceResource($service),
            ]);
        } catch (\Exception $e) {
            throw $e; // Let global handler return 404
        }
    }

    /**
     * Get all available categories
     * GET /api/services/categories/list
     */
    public function categories(): JsonResponse
    {
        $categories = SmmService::query()
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
