<?php

namespace Acelle\Plugin\AwsWhitelabel\Jobs;

use Acelle\Plugin\AwsWhitelabel\Main;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Async DELETE of proxy CNAMEs for a single sending domain that was just
 * deleted by the customer. Per-token DELETE so a single record mismatch
 * (TTL drift / manually edited) does not kill the whole batch.
 *
 * Dispatched from the SendingDomain::deleting Eloquent listener registered
 * in ServiceProvider::boot().
 */
class CleanupProxyCnamesForDomain implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 60;

    public function __construct(
        public string $domain,
        public array $tokens,
    ) {
    }

    public function handle(): void
    {
        $main = new Main();
        $logger = $main->logger();

        if (!$main->isFullyConfigured()) {
            $logger->info("Skip cleanup for {$this->domain}: plugin not fully configured");
            return;
        }

        $logger->info("Cleanup proxy CNAMEs for {$this->domain}", ['tokens' => $this->tokens]);
        $deleted = $main->deleteProxyCnamesForTokens($this->tokens);
        $logger->info("Cleanup done for {$this->domain}: deleted {$deleted} record(s)");
    }
}
