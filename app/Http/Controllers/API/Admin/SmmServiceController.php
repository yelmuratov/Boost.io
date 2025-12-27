<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmmService;
use App\Http\Resources\Smm\PublicSmmServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SmmServiceController extends Controller
{
    /**
     * Get all services (admin access - includes inactive)
     * GET /api/admin/services
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['is_active', 'provider_id', 'category', 'type', 'search']);

            $query = SmmService::with('provider:id,name,markup_percentage');

            // Apply filters
            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['provider_id'])) {
                $query->where('provider_id', $filters['provider_id']);
            }

            if (isset($filters['category'])) {
                $query->where('category', 'like', '%' . $filters['category'] . '%');
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('service_id', 'like', "%{$search}%");
                });
            }

            $query->orderBy('provider_id')->orderBy('category')->orderBy('name');

            $services = $query->paginate($perPage);

            // Enrich data with profit calculations for admin dashboard
            $enrichedData = $services->map(function ($service) {
                $cost = (float) $service->cost;
                $rate = (float) $service->rate;
                $profit = $rate - $cost;
                $profitPercentage = $cost > 0 ? ($profit / $cost) * 100 : 0;

                return [
                    'id' => $service->id,
                    'service_id' => $service->service_id,
                    'name' => $service->name,
                    'type' => $service->type,
                    'category' => $service->category,
                    'provider' => [
                        'id' => $service->provider->id,
                        'name' => $service->provider->name,
                        'markup_percentage' => (float) $service->provider->markup_percentage,
                    ],
                    'pricing' => [
                        'cost' => $cost,                    // What we pay provider
                        'rate' => $rate,                    // What customer pays
                        'profit' => round($profit, 4),      // Our profit
                        'profit_percentage' => round($profitPercentage, 2), // Profit %
                    ],
                    'limits' => [
                        'min' => $service->min,
                        'max' => $service->max,
                    ],
                    'is_active' => $service->is_active,
                    'description' => $service->description,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $enrichedData,
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services',
            ], 500);
        }
    }

    /**
     * Get single service by ID
     * GET /api/admin/services/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = SmmService::with('provider')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $service,
            ]);
        } catch (\Exception $e) {
            throw $e; // Let global handler return 404
        }
    }

    /**
     * Update service
     * PUT /api/admin/services/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'type' => 'sometimes|string|max:255',
                'category' => 'sometimes|string|max:255',
                'rate' => 'sometimes|numeric|min:0',
                'min' => 'sometimes|integer|min:0',
                'max' => 'sometimes|integer|min:0',
                'is_active' => 'sometimes|boolean',
                'description' => 'nullable|string',
            ]);

            $service = SmmService::findOrFail($id);
            $service->update($validated);

            return response()->json([
                'success' => true,
                'data' => $service->fresh(),
                'message' => 'Service updated successfully',
            ]);

        } catch (ValidationException $e) {
            throw $e; // Let global handler process
        } catch (\Exception $e) {
            throw $e; // Let global handler return 404 if not found
        }
    }

    /**
     * Toggle service active status
     * POST /api/admin/services/{id}/toggle-active
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $service = SmmService::findOrFail($id);
            $service->update(['is_active' => !$service->is_active]);

            return response()->json([
                'success' => true,
                'data' => $service->fresh(),
                'message' => $service->is_active ? 'Service activated' : 'Service deactivated',
            ]);
        } catch (\Exception $e) {
            throw $e; // Let global handler return 404
        }
    }

    /**
     * Delete service
     * DELETE /api/admin/services/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $service = SmmService::findOrFail($id);

            // Check if service has orders
            if ($service->orders()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete service with existing orders. Deactivate it instead.',
                ], 400);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully',
            ]);
        } catch (\Exception $e) {
            throw $e; // Let global handler return 404
        }
    }

    /**
     * Bulk update services (e.g., bulk activate/deactivate)
     * POST /api/admin/services/bulk-update
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'service_ids' => 'required|array',
                'service_ids.*' => 'integer|exists:smm_services,id',
                'is_active' => 'required|boolean',
            ]);

            $updated = SmmService::whereIn('id', $validated['service_ids'])
                ->update(['is_active' => $validated['is_active']]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$updated} services",
                'count' => $updated,
            ]);
        } catch (ValidationException $e) {
            throw $e; // Let global handler process
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update services',
            ], 500);
        }
    }
}
