<?php

namespace Acelle\Plugin\AwsWhitelabel\Jobs;

use Acelle\Plugin\AwsWhitelabel\Main;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Async UPSERT of proxy CNAMEs into the brand Route53 zone.
 *
 * Dispatched by `after_verify_dkim_against_aws_ses`. Per-token UPSERT so a
 * single failed token (eg. AWS rate limit) only retries that token, not the
 * whole batch.
 */
class UpsertProxyCnames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $domain,
        public array $tokens,
    ) {
    }

    public function handle(): void
    {
        $main = new Main();
        $logger = $main->logger();

        $logger->info("Start generating proxy DNS for {$this->domain}", ['tokens' => $this->tokens]);

        foreach ($this->tokens as $token) {
            try {
                $main->upsertProxyCname($token);
                $logger->info("UPSERT done: {$token} for {$this->domain}");
            } catch (\Aws\Exception\AwsException $e) {
                // Specific catch: AWS-side error during single-token UPSERT.
                // Re-throw so Laravel queue retries via $tries / $backoff.
                $logger->error("UPSERT failed: {$token} for {$this->domain}", [
                    'aws_error' => $e->getAwsErrorMessage(),
                    'aws_code' => $e->getAwsErrorCode(),
                ]);
                throw $e;
            }
        }
    }
}
