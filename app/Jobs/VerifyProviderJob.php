<?php

namespace App\Jobs;

use App\Models\SmmProvider;
use App\Services\SmmPanel\SmmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class VerifyProviderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff: 30s, 60s, 120s
     */
    public array $backoff = [30, 60, 120];

    /**
     * The maximum number of seconds the job can run (5 minutes total max).
     */
    public int $timeout = 60;

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
        Log::info("Verifying provider: {$this->provider->name}");

        try {
            $smmService = new SmmService($this->provider->api_url, $this->provider->api_key);

            // Test connection by getting balance
            $balanceData = $smmService->getBalance();
            $balance = $balanceData->balance ?? 0;
            $currency = $balanceData->currency ?? 'USD';

            // Update provider as verified
            $this->provider->update([
                'verification_status' => 'verified',
                'balance' => $balance,
                'metadata' => array_merge($this->provider->metadata ?? [], [
                    'currency' => $currency,
                    'verified_at' => now()->toISOString(),
                ]),
            ]);

            Log::info("Provider verified: {$this->provider->name}, Balance: {$balance} {$currency}");

            // Now dispatch the service sync job
            SyncProviderServicesJob::dispatch($this->provider);

        } catch (Exception $e) {
            Log::warning("Verification attempt failed for {$this->provider->name}: {$e->getMessage()}");
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(?Exception $exception): void
    {
        Log::error("Provider verification failed after all retries: {$this->provider->name}");

        $this->provider->update([
            'verification_status' => 'failed',
            'metadata' => array_merge($this->provider->metadata ?? [], [
                'verification_error' => $exception?->getMessage() ?? 'Unknown error',
                'verification_failed_at' => now()->toISOString(),
            ]),
        ]);
    }
}
