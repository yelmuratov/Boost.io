<?php

namespace App\Console\Commands;

use App\Models\RefreshToken;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class ClearExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired access tokens and refresh tokens';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting token cleanup process...');
        $this->newLine();

        // Clear expired personal access tokens
        $this->info('🔍 Checking personal access tokens...');
        $expiredAccessTokens = PersonalAccessToken::where('expires_at', '<', now())->get();
        $accessTokenCount = $expiredAccessTokens->count();

        if ($accessTokenCount > 0) {
            $bar = $this->output->createProgressBar($accessTokenCount);
            $bar->setFormat('verbose');
            $bar->start();

            foreach ($expiredAccessTokens as $token) {
                $token->delete();
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Deleted {$accessTokenCount} expired access token(s).");
        } else {
            $this->info("✅ No expired access tokens found.");
        }

        $this->newLine();

        // Clear expired refresh tokens
        $this->info('🔍 Checking refresh tokens...');
        $expiredRefreshTokens = RefreshToken::where('expires_at', '<', now())->get();
        $refreshTokenCount = $expiredRefreshTokens->count();

        if ($refreshTokenCount > 0) {
            $bar = $this->output->createProgressBar($refreshTokenCount);
            $bar->setFormat('verbose');
            $bar->start();

            foreach ($expiredRefreshTokens as $token) {
                $token->delete();
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Deleted {$refreshTokenCount} expired refresh token(s).");
        } else {
            $this->info("✅ No expired refresh tokens found.");
        }

        $this->newLine();
        $this->info("🎉 Token cleanup completed! Total deleted: " . ($accessTokenCount + $refreshTokenCount));

        return Command::SUCCESS;
    }
}
