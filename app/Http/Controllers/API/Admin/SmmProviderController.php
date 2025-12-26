<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SmmProviderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;

class SmmProviderController extends Controller
{
    public function __construct(
        private readonly SmmProviderService $providerService
    ) {
    }

    /**
     * Get all providers
     * GET /api/admin/providers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['is_active', 'search']);

            $providers = $this->providerService->getAllProviders($perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $providers->items(),
                'meta' => [
                    'current_page' => $providers->currentPage(),
                    'from' => $providers->firstItem(),
                    'last_page' => $providers->lastPage(),
                    'per_page' => $providers->perPage(),
                    'to' => $providers->lastItem(),
                    'total' => $providers->total(),
                ],
                'links' => [
                    'first' => $providers->url(1),
                    'last' => $providers->url($providers->lastPage()),
                    'prev' => $providers->previousPageUrl(),
                    'next' => $providers->nextPageUrl(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch providers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Get single provider
     * GET /api/admin/providers/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $provider = $this->providerService->getProvider($id);

            return response()->json([
                'success' => true,
                'data' => $provider,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Provider not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Create new provider
     * POST /api/admin/providers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'api_url' => 'required|url|max:500',
                'api_key' => 'required|string|max:500',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0|max:999',
                'metadata' => 'nullable|array',
            ]);

            $provider = $this->providerService->createProvider($validated);

            return response()->json([
                'success' => true,
                'data' => $provider,
                'message' => 'Provider created successfully',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create provider',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update provider
     * PUT /api/admin/providers/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'string|max:255',
                'api_url' => 'url|max:500',
                'api_key' => 'string|max:500',
                'is_active' => 'boolean',
                'priority' => 'integer|min:0|max:999',
                'metadata' => 'nullable|array',
            ]);

            $provider = $this->providerService->updateProvider($id, $validated);

            return response()->json([
                'success' => true,
                'data' => $provider,
                'message' => 'Provider updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update provider',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete provider
     * DELETE /api/admin/providers/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->providerService->deleteProvider($id);

            return response()->json([
                'success' => true,
                'message' => 'Provider deleted successfully',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete provider',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Sync provider services
     * POST /api/admin/providers/{id}/sync-services
     */
    public function syncServices(int $id): JsonResponse
    {
        try {
            $provider = $this->providerService->getProvider($id);
            $result = $this->providerService->syncProviderServices($provider);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Synced {$result['total']} services",
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync provider balance
     * POST /api/admin/providers/{id}/sync-balance
     */
    public function syncBalance(int $id): JsonResponse
    {
        try {
            $provider = $this->providerService->getProvider($id);
            $result = $this->providerService->syncProviderBalance($provider);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Balance synced successfully',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync balance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test provider connection
     * POST /api/admin/providers/{id}/test-connection
     */
    public function testConnection(int $id): JsonResponse
    {
        try {
            $provider = $this->providerService->getProvider($id);
            $result = $this->providerService->testConnection($provider);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Connection successful',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle provider active status
     * POST /api/admin/providers/{id}/toggle-active
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $provider = $this->providerService->toggleActive($id);

            return response()->json([
                'success' => true,
                'data' => $provider,
                'message' => $provider->is_active ? 'Provider activated' : 'Provider deactivated',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle provider status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get provider statistics
     * GET /api/admin/providers/{id}/stats
     */
    public function stats(int $id): JsonResponse
    {
        try {
            $stats = $this->providerService->getProviderStats($id);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all services from active providers
     * GET /api/services
     */
    public function getAllServices(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            $filters = $request->only(['category', 'type', 'search', 'provider_id']);

            $services = $this->providerService->getActiveServices($perPage, $filters);

            return response()->json([
                'success' => true,
                'data' => $services->items(),
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
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch services',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all available categories
     * GET /api/services/categories
     */
    public function getCategories(): JsonResponse
    {
        try {
            $categories = $this->providerService->getActiveCategories();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

