<?php
namespace App\Services\Admin;

use App\Models\SmmProvider;
use App\Services\SmmPanel\SmmService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;
use App\Models\SmmService as SmmServiceModel;
class SmmProviderService
{
    /**
     * Get all providers with statistics
     */
    public function getAllProviders(int $perPage = 15, array $filters = [])
    {
        $query = SmmProvider::withCount(['services', 'orders']);

        // Apply filters
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Sorting
        $query->orderBy('priority', 'desc')->orderBy('name');

        // Return paginated results
        return $query->paginate($perPage);
    }

    /**
     * Get single provider by ID
     */
    public function getProvider(int $id): SmmProvider
    {
        // Only load counts, not the actual relationships
        return SmmProvider::withCount(['services', 'orders'])
            ->findOrFail($id);
    }

    /**
     * Create new provider
     *
     * Provider is created immediately with status 'pending'.
     * A background job verifies the connection and syncs services.
     */
    public function createProvider(array $data): SmmProvider
    {
        $provider = SmmProvider::create([
            'name' => $data['name'],
            'api_url' => rtrim($data['api_url'], '/'),
            'api_key' => $data['api_key'],
            'is_active' => $data['is_active'] ?? true,
            'verification_status' => 'pending',
            'priority' => $data['priority'] ?? 0,
            'markup_percentage' => $data['markup_percentage'] ?? 25,
            'metadata' => $data['metadata'] ?? null,
        ]);

        // Dispatch verification job (runs in background with retries)
        \App\Jobs\VerifyProviderJob::dispatch($provider);

        return $provider;
    }

    /**
     * Update provider
     */
    public function updateProvider(int $id, array $data): SmmProvider
    {
        $provider = SmmProvider::findOrFail($id);

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['api_url'])) {
            $updateData['api_url'] = rtrim($data['api_url'], '/');
        }

        if (isset($data['api_key'])) {
            $updateData['api_key'] = $data['api_key'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (isset($data['priority'])) {
            $updateData['priority'] = $data['priority'];
        }

        if (isset($data['markup_percentage'])) {
            $updateData['markup_percentage'] = $data['markup_percentage'];
        }

        if (isset($data['metadata'])) {
            $updateData['metadata'] = $data['metadata'];
        }

        $provider->update($updateData);

        // Clear cache
        Cache::forget("provider_{$provider->id}_services");

        return $provider->fresh();
    }

    /**
     * Delete provider
     */
    public function deleteProvider(int $id): bool
    {
        $provider = SmmProvider::findOrFail($id);

        // Check if provider has orders
        if ($provider->orders()->exists()) {
            throw new Exception('Cannot delete provider with existing orders. Deactivate it instead.');
        }

        Cache::forget("provider_{$provider->id}_services");

        return $provider->delete();
    }

    /**
     * Sync provider services from API
     *
     * This also deletes services that no longer exist in the provider's response
     * to prevent users from ordering non-existent services.
     */
    public function syncProviderServices(SmmProvider $provider): array
    {
        $smmService = new SmmService($provider->api_url, $provider->api_key);

        $services = $smmService->getServices();

        $synced = 0;
        $updated = 0;
        $deleted = 0;
        $errors = [];
        $syncedServiceIds = [];

        DB::transaction(function () use ($provider, $services, &$synced, &$updated, &$errors, &$syncedServiceIds) {
            foreach ($services as $service) {
                try {
                    $serviceId = $service['service'] ?? $service['id'] ?? null;

                    if (!$serviceId) {
                        $errors[] = "Service missing ID";
                        continue;
                    }

                    $syncedServiceIds[] = $serviceId;

                    $exists = $provider->services()
                        ->where('service_id', $serviceId)
                        ->exists();

                    // Get provider's rate and apply markup
                    // IMPORTANT: Cast to float because provider API returns strings
                    $providerRate = (float) ($service['rate'] ?? 0);
                    $markupPercentage = $provider->markup_percentage ?? 25;
                    $customerRate = $providerRate * (1 + $markupPercentage / 100);

                    $provider->services()->updateOrCreate(
                        [
                            'service_id' => $serviceId,
                        ],
                        [
                            'name' => $service['name'] ?? 'Unknown',
                            'type' => $service['type'] ?? 'default',
                            'category' => $service['category'] ?? null,
                            'cost' => $providerRate,  // What we pay the provider
                            'rate' => $customerRate,   // What customer pays (with markup)
                            'min' => $service['min'] ?? null,
                            'max' => $service['max'] ?? null,
                            'description' => $service['description'] ?? null,
                            'is_active' => true,
                            'metadata' => $service,
                        ]
                    );

                    if ($exists) {
                        $updated++;
                    } else {
                        $synced++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Service {$serviceId}: {$e->getMessage()}";
                }
            }
        });

        // Delete services that no longer exist in the provider's response
        if (!empty($syncedServiceIds)) {
            $deleted = $provider->services()
                ->whereNotIn('service_id', $syncedServiceIds)
                ->delete();
        }

        $provider->update(['last_sync_at' => now()]);
        Cache::forget("provider_{$provider->id}_services");

        return [
            'created' => $synced,
            'updated' => $updated,
            'deleted' => $deleted,
            'total' => $synced + $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Sync provider balance
     */
    public function syncProviderBalance(SmmProvider $provider): array
    {
        $smmService = new SmmService($provider->api_url, $provider->api_key);

        $balanceData = $smmService->getBalance();

        $balance = $balanceData->balance ?? $balanceData->currency ?? 0;
        $currency = $balanceData->currency ?? 'USD';

        $provider->update([
            'balance' => $balance,
            'metadata' => array_merge($provider->metadata ?? [], [
                'currency' => $currency,
                'last_balance_check' => now()->toISOString(),
            ]),
        ]);

        return [
            'balance' => (float) $balance,
            'currency' => $currency,
        ];
    }

    /**
     * Test provider connection
     */
    public function testConnection(SmmProvider $provider): array
    {
        $smmService = new SmmService($provider->api_url, $provider->api_key);

        $balance = $smmService->getBalance();

        return [
            'success' => true,
            'balance' => $balance->balance ?? 0,
            'currency' => $balance->currency ?? 'USD',
            'message' => 'Connection successful',
        ];
    }

    /**
     * Toggle provider active status
     */
    public function toggleActive(int $id): SmmProvider
    {
        $provider = SmmProvider::findOrFail($id);
        $provider->update(['is_active' => !$provider->is_active]);

        return $provider->fresh();
    }

    /**
     * Get provider statistics
     */
    public function getProviderStats(int $id): array
    {
        $provider = SmmProvider::findOrFail($id);

        return [
            'total_services' => $provider->services()->count(),
            'active_services' => $provider->services()->where('is_active', true)->count(),
            'total_orders' => $provider->orders()->count(),
            'pending_orders' => $provider->orders()->where('status', 'pending')->count(),
            'completed_orders' => $provider->orders()->where('status', 'completed')->count(),
            'total_revenue' => $provider->orders()->sum('charge'),
            'total_cost' => $provider->orders()->sum('cost'),
            'balance' => $provider->balance,
        ];
    }

    /**
     * Get all services from active providers
     *
     * Only returns services where:
     * - The service is active (is_active = true)
     * - The provider is active (is_active = true)
     */
    public function getActiveServices(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = SmmServiceModel::query()
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_active', true);

        // Apply category filter
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Apply type filter
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply search filter
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Apply provider filter
        if (isset($filters['provider_id'])) {
            $query->where('provider_id', $filters['provider_id']);
        }

        // Sorting
        $query->orderBy('category')->orderBy('name');

        return $query->paginate($perPage);
    }

    /**
     * Get all unique categories from active services
     */
    public function getActiveCategories(): array
    {
        return SmmServiceModel::query()
            ->whereHas('provider', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_active', true)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values()
            ->toArray();
    }
}

