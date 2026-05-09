<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Acelle\Plugin\AwsWhitelabel\Jobs\UpsertProxyCnames;
use App\Model\Plugin as PluginModel;
use App\Model\SendingServer;
use Aws\Route53\Route53Client;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * AWS Whitelabel — service layer.
 *
 * Plugin-wide config lives in the `plugins` row's JSON `data` column:
 *   - aws_key, aws_secret: IAM creds with Route53 ChangeResourceRecordSets perm
 *   - domain, zone:        the brand domain (must be a Route53 hosted zone)
 *
 * Runtime contract — the plugin participates in two flows:
 *   1. Customer DNS records page: filter_aws_ses_dns_records rewrites
 *      `[token].dkim.amazonses.com` → `[token].dkim.{brand}` so the customer
 *      sees a brand-domain CNAME instead of "amazonses.com". SPF and identity
 *      records are passed through unchanged (deliberately — customer still
 *      needs them).
 *   2. After SES verifies DKIM: after_verify_dkim_against_aws_ses dispatches
 *      a queued job that UPSERTs the proxy CNAME chain in Route53.
 */
class Main
{
    public const NAME = 'acelle/awswhitelabel';

    public function logger(): Logger
    {
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");

        $logfile = storage_path('logs/awswhitelabel.log');
        $stream = new RotatingFileHandler($logfile, 0, Logger::DEBUG);
        $stream->setFormatter($formatter);

        $logger = new Logger((string) getmypid());
        $logger->pushHandler($stream);

        return $logger;
    }

    public function getDbRecord(): ?PluginModel
    {
        return PluginModel::where('name', self::NAME)->first();
    }

    /**
     * Plugin runtime should rewrite DKIM records?
     * Active = DB row exists, status=active, AND fully configured (creds + zone + domain).
     */
    public function isFullyConfigured(): bool
    {
        $record = $this->getDbRecord();
        if (!$record || !$record->isActive()) {
            return false;
        }
        $data = $record->getData();
        return isset($data['aws_key'], $data['aws_secret'], $data['zone'], $data['domain']);
    }

    /**
     * Hook handler: filter_aws_ses_dns_records.
     *
     * Rewrites the DKIM CNAME values from `*.dkim.amazonses.com` to
     * `*.dkim.{brand}`. SPF and identity TXT records are passed through
     * unchanged — customer still needs them for SES authentication.
     */
    public function rewriteDkimRecords(array &$dkims): void
    {
        $data = $this->getDbRecord()->getData();
        $domain = $data['domain'];

        foreach ($dkims as $i => $dkim) {
            $dkim['value'] = str_replace('.dkim.amazonses.com', ".dkim.{$domain}", $dkim['value']);
            $dkims[$i] = $dkim;
        }
    }

    /**
     * Hook handler: after_verify_dkim_against_aws_ses.
     *
     * Dispatches a queued job to UPSERT the proxy CNAME chain in Route53.
     * Async so customer's "Verify DKIM" request returns immediately —
     * Route53 latency / outage cannot block the customer.
     */
    public function dispatchProxyCnameUpsert(string $domain, array $tokens): void
    {
        UpsertProxyCnames::dispatch($domain, $tokens);
    }

    public function getRoute53Client(): Route53Client
    {
        $data = $this->getDbRecord()->getData();

        if (!isset($data['aws_key'], $data['aws_secret'])) {
            throw new \RuntimeException('Plugin AWS Whitelabel: AWS credentials not configured');
        }

        return self::initRoute53Client($data['aws_key'], $data['aws_secret']);
    }

    public static function initRoute53Client(string $key, string $secret): Route53Client
    {
        return new Route53Client([
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
            'region' => 'us-east-1',
            'version' => '2013-04-01',
        ]);
    }

    /**
     * UPSERT a single proxy CNAME: `{token}.dkim.{brand}` → `{token}.dkim.amazonses.com`.
     * Called by UpsertProxyCnames job (per-token).
     */
    public function upsertProxyCname(string $token): void
    {
        $data = $this->getDbRecord()->getData();
        $brandDomain = $data['domain'];
        $hostedzone = $data['zone'];

        $name = "{$token}.dkim.{$brandDomain}";
        $value = "{$token}.dkim.amazonses.com";

        $this->getRoute53Client()->changeResourceRecordSets([
            'HostedZoneId' => $hostedzone,
            'ChangeBatch' => [
                'Comment' => 'Acelle AWS Whitelabel — proxy CNAME UPSERT',
                'Changes' => [
                    [
                        'Action' => 'UPSERT',
                        'ResourceRecordSet' => [
                            'Name' => $name,
                            'Type' => 'CNAME',
                            'TTL' => 600,
                            'ResourceRecords' => [['Value' => $value]],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Bulk-delete every CNAME in the configured brand zone whose value points
     * at `*.dkim.amazonses.com`. Called on plugin delete to clean up orphans.
     */
    public function cleanupAllProxyCnames(): int
    {
        $data = $this->getDbRecord()->getData();
        if (!isset($data['zone'])) {
            return 0;
        }
        return $this->cleanupProxyCnamesInZone($data['zone']);
    }

    /**
     * Bulk-delete every CNAME in a specific Route53 hosted zone whose value
     * points at `*.dkim.amazonses.com`. Used for both plugin-delete cleanup
     * (current zone) and brand-domain-change cleanup (old zone).
     *
     * Returns the count of records deleted.
     */
    public function cleanupProxyCnamesInZone(string $hostedzone): int
    {
        $client = $this->getRoute53Client();

        $deleted = 0;
        $startRecord = null;

        do {
            $params = ['HostedZoneId' => $hostedzone];
            if ($startRecord) {
                $params['StartRecordName'] = $startRecord['name'];
                $params['StartRecordType'] = $startRecord['type'];
            }

            $result = $client->listResourceRecordSets($params);
            $changes = [];

            foreach ($result['ResourceRecordSets'] as $rrset) {
                if ($rrset['Type'] !== 'CNAME') {
                    continue;
                }
                $values = array_column($rrset['ResourceRecords'] ?? [], 'Value');
                $isProxy = collect($values)->contains(fn ($v) => str_ends_with($v, '.dkim.amazonses.com'));
                if (!$isProxy) {
                    continue;
                }

                $changes[] = [
                    'Action' => 'DELETE',
                    'ResourceRecordSet' => $rrset,
                ];
            }

            if (!empty($changes)) {
                $client->changeResourceRecordSets([
                    'HostedZoneId' => $hostedzone,
                    'ChangeBatch' => [
                        'Comment' => 'Acelle AWS Whitelabel — bulk proxy CNAME cleanup',
                        'Changes' => $changes,
                    ],
                ]);
                $deleted += count($changes);
            }

            if (!empty($result['IsTruncated'])) {
                $startRecord = [
                    'name' => $result['NextRecordName'],
                    'type' => $result['NextRecordType'],
                ];
            } else {
                $startRecord = null;
            }
        } while ($startRecord);

        $this->logger()->info("Cleanup complete: deleted {$deleted} proxy CNAMEs from zone {$hostedzone}");

        return $deleted;
    }

    /**
     * Delete proxy CNAMEs for a specific set of tokens — used when a customer
     * deletes a sending domain. Listed records are scoped to the configured
     * brand zone; tokens are the SES-issued DKIM tokens (eg ['abc123','def456']).
     *
     * Per-token DELETE so a single record mismatch (TTL drift, manually edited)
     * doesn't kill the whole batch — failures are logged + skipped.
     */
    public function deleteProxyCnamesForTokens(array $tokens): int
    {
        $data = $this->getDbRecord()->getData();
        if (!isset($data['zone'], $data['domain']) || empty($tokens)) {
            return 0;
        }

        $brand = $data['domain'];
        $hostedzone = $data['zone'];
        $client = $this->getRoute53Client();

        $deleted = 0;
        foreach ($tokens as $token) {
            try {
                $client->changeResourceRecordSets([
                    'HostedZoneId' => $hostedzone,
                    'ChangeBatch' => [
                        'Comment' => "Acelle AWS Whitelabel — proxy CNAME delete for token {$token}",
                        'Changes' => [[
                            'Action' => 'DELETE',
                            'ResourceRecordSet' => [
                                'Name' => "{$token}.dkim.{$brand}",
                                'Type' => 'CNAME',
                                'TTL' => 600,
                                'ResourceRecords' => [['Value' => "{$token}.dkim.amazonses.com"]],
                            ],
                        ]],
                    ],
                ]);
                $deleted++;
            } catch (\Aws\Exception\AwsException $e) {
                // Specific catch: record may already be missing (NoSuchRRSet)
                // or TTL/value drift since we wrote it (InvalidChangeBatch).
                // Both non-fatal — log and continue with the next token.
                $this->logger()->warning(
                    "delete proxy CNAME failed: {$token}.dkim.{$brand}",
                    ['code' => $e->getAwsErrorCode(), 'msg' => $e->getAwsErrorMessage()]
                );
            }
        }

        return $deleted;
    }

    /**
     * Compare the brand domain's authoritative NS records (from Route53) vs
     * its public DNS (resolver lookup). If the registrar has not delegated
     * the domain to Route53, the proxy CNAMEs we write will be invisible to
     * resolvers, and customer DKIM verification will fail with no diagnostic.
     *
     * Returns ['delegated' => bool, 'route53_ns' => list<string>, 'public_ns' => list<string>].
     * 'public_ns' may be empty if the resolver could not find any NS records
     * (eg. domain doesn't exist) — treat that as not-delegated.
     */
    public function checkNsDelegation(string $hostedzone, string $brandDomain): array
    {
        $client = $this->getRoute53Client();

        // Route53's authoritative NS for the hosted zone.
        $hzInfo = $client->getHostedZone(['Id' => $hostedzone]);
        $route53Ns = collect($hzInfo['DelegationSet']['NameServers'] ?? [])
            ->map(fn ($n) => strtolower(rtrim($n, '.')))
            ->all();

        // Public NS records for the brand domain.
        $records = @dns_get_record($brandDomain, DNS_NS) ?: [];
        $publicNs = collect($records)
            ->pluck('target')
            ->filter()
            ->map(fn ($n) => strtolower(rtrim($n, '.')))
            ->all();

        $overlap = array_intersect($route53Ns, $publicNs);
        $delegated = !empty($overlap);

        return [
            'delegated' => $delegated,
            'route53_ns' => $route53Ns,
            'public_ns' => $publicNs,
        ];
    }

    public function getRoute53Domains(): array
    {
        $results = $this->getRoute53Client()->listHostedZones();

        if (!isset($results['HostedZones'])) {
            return [];
        }

        return array_map(
            function ($e) {
                return [
                    'zone' => str_replace('/hostedzone/', '', $e['Id']),
                    'name' => rtrim($e['Name'], '.'),
                ];
            },
            $results['HostedZones']
        );
    }

    public function testRoute53Connection(string $keyId, string $secret): void
    {
        $client = self::initRoute53Client($keyId, $secret);
        $client->listHostedZones();
    }

    public function connectAndSave(string $keyId, string $secret): void
    {
        $this->testRoute53Connection($keyId, $secret);

        $record = $this->getDbRecord();
        $record->updateData([
            'aws_key' => $keyId,
            'aws_secret' => $secret,
        ]);
    }

    public function updateDomain(string $domainAndZone): void
    {
        [$domain, $zone] = explode('|', $domainAndZone);
        $record = $this->getDbRecord();
        $record->updateData([
            'domain' => $domain,
            'zone' => $zone,
        ]);
    }

    /**
     * Lifecycle: validate config on activation. Throws if creds bad or
     * configured zone is not present in the AWS account — Plugin::activate()
     * propagates the exception so admin sees the failure inline.
     */
    public function onActivate(): void
    {
        $data = $this->getDbRecord()->getData();

        if (!isset($data['aws_key'], $data['aws_secret'])) {
            throw new \RuntimeException('AWS credentials not configured. Open plugin settings to configure before activating.');
        }
        if (!isset($data['zone'])) {
            throw new \RuntimeException('Brand domain / Route53 zone not configured. Open plugin settings to select a zone before activating.');
        }

        $zones = $this->getRoute53Domains();
        if (empty($zones)) {
            throw new \RuntimeException('AWS account has no Route53 hosted zones. Verify the credentials point at the correct AWS account.');
        }

        $found = collect($zones)->contains(fn ($z) => $z['zone'] === $data['zone']);
        if (!$found) {
            throw new \RuntimeException("Configured zone '{$data['zone']}' was not found in the AWS account. Re-select brand domain in plugin settings.");
        }
    }

    /**
     * Lifecycle: bulk-cleanup on plugin delete. Best-effort — logs errors
     * but does not throw, since failing to delete proxy CNAMEs should not
     * prevent the plugin from being uninstalled.
     */
    public function onDelete(): void
    {
        try {
            $this->cleanupAllProxyCnames();
        } catch (\Throwable $e) {
            // Specific catch: cleanup is best-effort during uninstall.
            // Failure to reach Route53 (creds revoked, zone deleted, network)
            // must not block plugin deletion. Log + continue.
            \Log::warning('AWS Whitelabel cleanup on delete failed', [
                'plugin' => self::NAME,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect: is the current request a sending-server edit page for an SES
     * vendor (amazon-api / amazon-smtp)? Returns the SendingServer when yes,
     * null when no. Used by the layout.body.before_close hook to gate the
     * admin-facing status chip + modal render.
     */
    public function detectSesSendingServer(): ?SendingServer
    {
        $route = request()->route();
        $name = $route?->getName();

        $allowedRoutes = [
            'refactor.admin.sending-servers.edit',
            'refactor.sending.server_edit',
        ];
        if (!in_array($name, $allowedRoutes, true)) {
            return null;
        }

        $uid = $route->parameter('uid');
        if (!$uid) {
            return null;
        }

        $server = SendingServer::findByUid($uid);
        if (!$server) {
            return null;
        }
        if (!in_array($server->type, ['amazon-api', 'amazon-smtp'], true)) {
            return null;
        }

        return $server;
    }

    /**
     * Detect: is the current request the customer-facing DNS records page
     * for a sending identity owned by an SES sending server? Returns the
     * SendingIdentity when yes, null otherwise. Used by the layout.body.before_close
     * hook to gate the customer-facing info chip + modal render — explains
     * to the customer that their DKIM CNAMEs are proxied via the brand domain.
     */
    public function detectSesSendingDomain(): ?\App\Model\SendingIdentity
    {
        $route = request()->route();
        if ($route?->getName() !== 'refactor.sending.domain_records') {
            return null;
        }

        $uid = $route->parameter('uid');
        if (!$uid) {
            return null;
        }

        $identity = \App\Model\SendingIdentity::query()->where('uid', $uid)->first();
        if (!$identity) {
            return null;
        }

        // Only show the chip when the identity's DKIM records look like SES
        // (CNAME records pointing at amazonses.com). STANDARD-vendor domains
        // produce TXT-style DKIM and have nothing to do with whitelabelling.
        $hasSesDkim = false;
        foreach ($identity->recordsByPurpose(\App\SendingServers\DomainVerification\RecordPurpose::DKIM) as $r) {
            if (strtoupper($r->type) === 'CNAME'
                && is_string($r->value)
                && str_contains($r->value, '.dkim.amazonses.com')) {
                $hasSesDkim = true;
                break;
            }
        }
        if (!$hasSesDkim) {
            return null;
        }

        return $identity;
    }
}
