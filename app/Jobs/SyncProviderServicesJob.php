<?php

namespace App\Jobs;

use App\Models\SmmProvider;
use App\Services\SmmPanel\SmmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncProviderServicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SmmProvider $provider
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting sync for provider: {$this->provider->name}");

        try {
            $smmService = new SmmService($this->provider->api_url, $this->provider->api_key);
            $services = $smmService->getServices();

            $syncedServiceIds = [];
            $synced = 0;
            $updated = 0;
            $deleted = 0;

            DB::transaction(function () use ($services, &$syncedServiceIds, &$synced, &$updated) {
                foreach ($services as $service) {
                    $serviceId = $service['service'] ?? $service['id'] ?? null;

                    if (!$serviceId) {
                        continue;
                    }

                    $syncedServiceIds[] = $serviceId;

                    $exists = $this->provider->services()
                        ->where('service_id', $serviceId)
                        ->exists();

                    $this->provider->services()->updateOrCreate(
                        ['service_id' => $serviceId],
                        [
                            'name' => $service['name'] ?? 'Unknown',
                            'type' => $service['type'] ?? 'default',
                            'category' => $service['category'] ?? null,
                            'rate' => $service['rate'] ?? 0,
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
                }
            });

            // Delete services that no longer exist in the provider's response
            if (!empty($syncedServiceIds)) {
                $deleted = $this->provider->services()
                    ->whereNotIn('service_id', $syncedServiceIds)
                    ->delete();
            }

            // Update last sync time
            $this->provider->update(['last_sync_at' => now()]);
            Cache::forget("provider_{$this->provider->id}_services");

            // Also sync balance
            try {
                $balanceData = $smmService->getBalance();
                $balance = $balanceData->balance ?? 0;
                $currency = $balanceData->currency ?? 'USD';

                $this->provider->update([
                    'balance' => $balance,
                    'metadata' => array_merge($this->provider->metadata ?? [], [
                        'currency' => $currency,
                        'last_balance_check' => now()->toISOString(),
                    ]),
                ]);
            } catch (Exception $e) {
                Log::warning("Balance sync failed for {$this->provider->name}: {$e->getMessage()}");
            }

            Log::info("Sync completed for {$this->provider->name}: created={$synced}, updated={$updated}, deleted={$deleted}");

        } catch (Exception $e) {
            Log::error("Sync failed for provider {$this->provider->name}: {$e->getMessage()}");
            throw $e; // Re-throw to trigger retry
        }
    }
}
