<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Acelle\Plugin\AwsWhitelabel\Jobs\CleanupProxyCnamesForDomain;
use App\Library\Facades\Hook;
use App\Model\SendingDomain;
use Illuminate\Support\ServiceProvider as Base;

class ServiceProvider extends Base
{
    public function register(): void
    {
        Hook::add('add_translation_file', function () {
            return [
                'id' => '#acelle/awswhitelabel_translation_file',
                'plugin_name' => Main::NAME,
                'file_title' => 'Translation for acelle/awswhitelabel plugin',
                'translation_folder' => storage_path('app/data/plugins/acelle/awswhitelabel/lang/'),
                'translation_prefix' => 'awswhitelabel',
                'file_name' => 'messages.php',
                'master_translation_file' => realpath(__DIR__.'/../resources/lang/en/messages.php'),
            ];
        });
    }

    public function boot(): void
    {
        $main = new Main();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'awswhitelabel');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'awswhitelabel');
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        $this->publishes([
            __DIR__.'/../assets' => public_path('vendor/awswhitelabel'),
        ], 'public');

        // Plugin-icon hook — surfaces the icon URL on the admin Plugins page.
        // Served by the in-folder route below, so works whether the plugin
        // is copied or symlinked into storage/app/plugins/.
        Hook::set('icon_url_'.Main::NAME, fn () => route('plugin.acelle.awswhitelabel.icon'));

        // ---------- Lifecycle hooks (always-on, no isActive() gate) ----------
        // Activation handler validates config + AWS credentials. Throws on
        // failure so Plugin::activate() bubbles the error to the admin UI.
        Hook::on('activate_plugin_'.Main::NAME, fn () => $main->onActivate());

        // Delete handler bulk-removes proxy CNAMEs from the brand zone so
        // uninstall does not leave orphan records in Route53.
        Hook::on('delete_plugin_'.Main::NAME, fn () => $main->onDelete());

        // ---------- Runtime hooks (always register, gate inside closure) ----------
        // Each closure checks `isFullyConfigured()` so disabling the plugin
        // (or wiping its config) immediately stops side-effects without
        // requiring an app restart.

        // Customer DNS records page: rewrite DKIM CNAME values from
        // `*.dkim.amazonses.com` → `*.dkim.{brand}`. SPF + identity records
        // are passed through unchanged — customer still needs them.
        Hook::add('filter_aws_ses_dns_records', function (&$identity, &$dkims, &$spf) use ($main) {
            if (!$main->isFullyConfigured()) {
                return;
            }
            $main->rewriteDkimRecords($dkims);
        });

        // After SES has issued DKIM tokens for a customer's domain: dispatch
        // async job to UPSERT the proxy CNAME chain in Route53. Tokens are
        // issued BEFORE the customer paste records into their DNS — proxy
        // bridge must exist by the time their DNS chain hits it. Async so
        // customer's request does not block on Route53 latency / outage.
        Hook::on('aws_ses_dkim_tokens_issued', function ($domain, $tokens) use ($main) {
            if (!$main->isFullyConfigured()) {
                return;
            }
            $main->dispatchProxyCnameUpsert($domain, $tokens);
        });

        // Universal layout slot: render context-appropriate chip + modal.
        //
        // Two detection paths:
        //   1. Sending-server edit page (admin or customer) for an SES vendor →
        //      admin-facing status chip ("Whitelabelled · brand" or "Not
        //      whitelabelled") + modal with detail and Settings CTA.
        //   2. Customer DNS-records page for a domain owned by an SES server,
        //      AND plugin is fully configured → customer-facing info chip
        //      ("DKIM proxied via brand") + modal explaining the rewrite.
        //
        // Both share `awswl-chip` styling but use distinct view templates and
        // modal IDs so the two never collide on the same page.
        Hook::add('layout.body.before_close', function ($layout = 'app') use ($main) {
            if ($server = $main->detectSesSendingServer()) {
                return view('awswhitelabel::status_modal', [
                    'server' => $server,
                    'plugin' => $main->getDbRecord(),
                    'configured' => $main->isFullyConfigured(),
                ])->render();
            }

            if ($sendingDomain = $main->detectSesSendingDomain()) {
                if (!$main->isFullyConfigured()) {
                    return null;
                }
                return view('awswhitelabel::info_modal', [
                    'sendingDomain' => $sendingDomain,
                    'plugin' => $main->getDbRecord(),
                ])->render();
            }

            return null;
        });

        // Inject chip CSS into <head>. Cheap (single file) and only on pages
        // that may show the chip — but still simpler to inject globally
        // since CSS without matching DOM is inert.
        Hook::add('layout.head.assets', function ($layout = 'app') {
            return '<link rel="stylesheet" href="'.asset('vendor/awswhitelabel/css/awswl-chip.css').'">';
        });

        // Per-domain cleanup: when a customer deletes a sending domain, the
        // proxy CNAMEs we wrote into the brand zone for that domain's DKIM
        // tokens become orphans. Dispatch an async DELETE so customer's
        // request doesn't block on Route53 + plugin can fail-isolated.
        SendingDomain::deleting(function (SendingDomain $sendingDomain) use ($main) {
            if (!$main->isFullyConfigured()) {
                return;
            }

            $tokens = $sendingDomain->getVerificationTokens();
            if (!is_array($tokens) || empty($tokens['dkim'])) {
                return;
            }

            // Extract SES DKIM token prefixes from records of shape
            //   ['type'=>'CNAME', 'name'=>'tok._domainkey.X', 'value'=>'tok.dkim.amazonses.com']
            // Skip non-AWS DKIM (eg. STANDARD vendor uses TXT records).
            $tokenList = [];
            foreach ($tokens['dkim'] as $dkim) {
                if (($dkim['type'] ?? null) !== 'CNAME') {
                    continue;
                }
                if (preg_match('/^([A-Za-z0-9]+)\.dkim\.amazonses\.com$/', $dkim['value'] ?? '', $m)) {
                    $tokenList[] = $m[1];
                }
            }

            if (empty($tokenList)) {
                return;
            }

            CleanupProxyCnamesForDomain::dispatch($sendingDomain->name, $tokenList);
        });
    }
}
