<?php

namespace App\Console\Commands;

use App\Jobs\SyncProviderServicesJob;
use App\Models\SmmProvider;
use Illuminate\Console\Command;

class SyncSmmServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-smm-services {--sync : Run synchronously instead of dispatching jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync SMM services from all active providers to the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Starting SMM services sync...');
        $this->newLine();

        // Get all active providers
        $providers = SmmProvider::where('is_active', true)->get();

        if ($providers->isEmpty()) {
            $this->warn('No active providers found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$providers->count()} active provider(s).");
        $this->newLine();

        $runSync = $this->option('sync');

        foreach ($providers as $provider) {
            if ($runSync) {
                $this->info("📦 Syncing: {$provider->name} (synchronously)...");
                try {
                    dispatch_sync(new SyncProviderServicesJob($provider));
                    $this->info("   ✅ Completed");
                } catch (\Exception $e) {
                    $this->error("   ❌ Failed: {$e->getMessage()}");
                }
            } else {
                $this->info("📦 Dispatching job for: {$provider->name}");
                SyncProviderServicesJob::dispatch($provider);
            }
        }

        $this->newLine();

        if ($runSync) {
            $this->info('🎉 SMM services sync completed!');
        } else {
            $this->info('🎉 Sync jobs dispatched! Check your queue worker for progress.');
            $this->info('   Run with --sync flag to execute synchronously.');
        }

        return Command::SUCCESS;
    }
}

