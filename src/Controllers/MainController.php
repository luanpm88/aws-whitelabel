<?php

namespace Acelle\Plugin\AwsWhitelabel\Controllers;

use Acelle\Plugin\AwsWhitelabel\Main;
use App\Http\Controllers\Controller;
use App\Model\Plugin;
use Illuminate\Http\Request;

class MainController extends Controller
{
    /**
     * Settings page — wizard router. Steps:
     *   1. No creds → editKey form
     *   2. Creds set, no zone → selectDomain form
     *   3. Both set → summary card (with Test Connection / Edit creds / Disconnect)
     */
    public function index(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();

        if (!isset($data['aws_key'], $data['aws_secret'])) {
            return redirect()->action([self::class, 'editKey']);
        }

        if (!isset($data['zone'], $data['domain'])) {
            return redirect()->action([self::class, 'selectDomain']);
        }

        return view('awswhitelabel::index', [
            'plugin' => $record,
            'data' => $data,
            'maskedKey' => self::maskCredential($data['aws_key']),
        ]);
    }

    public function editKey(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();

        return view('awswhitelabel::edit', [
            'plugin' => $record,
            'data' => $data,
            'hasExistingCreds' => isset($data['aws_key']),
            'maskedKey' => isset($data['aws_key']) ? self::maskCredential($data['aws_key']) : null,
            'step' => isset($data['zone']) ? null : 1, // wizard hint
        ]);
    }

    public function selectDomain(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $data = $record->getData();

        try {
            $domains = $main->getRoute53Domains();
        } catch (\Aws\Exception\AwsException $e) {
            // Specific catch: Route53 list call failed (creds revoked, network).
            // Surface to admin via flash + redirect to credential edit.
            $request->session()->flash('alert-error', 'Could not list Route53 zones: '.$e->getAwsErrorMessage());
            return redirect()->action([self::class, 'editKey']);
        }

        // NS-delegation check on the currently-configured zone (if any).
        // If the brand domain's public NS records don't include any of the
        // Route53 hosted-zone NS records, the proxy CNAMEs are invisible to
        // public resolvers — customer DKIM verification will silently fail.
        $delegation = null;
        if (isset($data['zone'], $data['domain'])) {
            try {
                $delegation = $main->checkNsDelegation($data['zone'], $data['domain']);
            } catch (\Throwable $e) {
                // Specific catch: NS check is best-effort diagnostics; if Route53
                // getHostedZone or dns_get_record fails, we still want the page
                // to render. Log + skip the warning.
                \Log::warning('AWS Whitelabel: NS delegation check failed', [
                    'zone' => $data['zone'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('awswhitelabel::select', [
            'plugin' => $record,
            'data' => $data,
            'domains' => $domains,
            'currentZone' => $data['zone'] ?? null,
            'delegation' => $delegation,
            'step' => isset($data['aws_key']) ? 2 : null,
        ]);
    }

    public function saveKey(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'aws_key' => 'required',
            'aws_secret' => 'required',
        ]);

        if ($validator->fails()) {
            $error = print_r(array_values($validator->errors()->toArray()), true);
            $request->session()->flash('alert-error', $error);
            return redirect()->action([self::class, 'editKey']);
        }

        $main = new Main();
        try {
            $main->connectAndSave($request->input('aws_key'), $request->input('aws_secret'));
        } catch (\Aws\Exception\AwsException $e) {
            // Specific catch: AWS-side credential / permission rejection.
            $request->session()->flash('alert-error', 'Cannot connect to AWS Route53: '.$e->getAwsErrorMessage());
            return redirect()->action([self::class, 'editKey']);
        }

        $request->session()->flash('alert-success', 'Connected to AWS Route53');
        return redirect()->action([self::class, 'selectDomain']);
    }

    public function saveDomain(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'domain' => 'required',
        ]);

        if ($validator->fails()) {
            $error = print_r(array_values($validator->errors()->toArray()), true);
            $request->session()->flash('alert-error', $error);
            return redirect()->action([self::class, 'selectDomain']);
        }

        $main = new Main();
        $record = $main->getDbRecord();
        $oldData = $record->getData();
        $oldZone = $oldData['zone'] ?? null;

        // Detect zone change. If admin switches to a different brand domain,
        // proxy CNAMEs in the OLD zone are orphaned — customer-side DNS chains
        // still point there but we won't UPSERT into them anymore. Cleanup
        // before switching so admin doesn't accumulate dead records over time.
        $parts = explode('|', $request->input('domain'));
        $newZone = $parts[1] ?? null;
        $cleanupNotice = null;

        if ($oldZone && $newZone && $oldZone !== $newZone) {
            try {
                $deleted = $main->cleanupProxyCnamesInZone($oldZone);
                if ($deleted > 0) {
                    $cleanupNotice = "Cleaned up {$deleted} proxy CNAME(s) from previous brand zone {$oldZone}.";
                }
            } catch (\Aws\Exception\AwsException $e) {
                // Specific catch: cleanup of old zone failed (perms revoked,
                // zone deleted). Non-fatal — proceed with switch but warn admin.
                \Log::warning('AWS Whitelabel: old-zone cleanup failed during switch', [
                    'old_zone' => $oldZone,
                    'error' => $e->getAwsErrorMessage(),
                ]);
                $request->session()->flash('alert-warning',
                    "Brand domain switched, but cleanup of old zone {$oldZone} failed: ".$e->getAwsErrorMessage()
                    .'. You may have orphan proxy CNAMEs in that zone.'
                );
            }
        }

        $main->updateDomain($request->input('domain'));

        // Auto-activate when both creds + zone are present and plugin is currently inactive.
        // Drops the redundant "Activate" button — completing setup means activating.
        if (!$record->isActive()) {
            try {
                $record->activate();
            } catch (\Throwable $e) {
                // Specific catch: activation hook (Main::onActivate) ran the
                // config validation and rejected. Show the reason inline.
                $request->session()->flash('alert-error', 'Setup saved but activation failed: '.$e->getMessage());
                return redirect()->action([self::class, 'index']);
            }
        }

        $msg = 'Brand domain saved — whitelabel is active.';
        if ($cleanupNotice) {
            $msg .= ' '.$cleanupNotice;
        }
        $request->session()->flash('alert-success', $msg);
        return redirect()->action([self::class, 'index']);
    }

    /**
     * Test the saved credentials against Route53 without re-entering them.
     * Returns JSON for inline rendering on the index page.
     */
    public function testConnection(Request $request)
    {
        $main = new Main();
        $data = $main->getDbRecord()->getData();

        if (!isset($data['aws_key'], $data['aws_secret'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'No credentials saved.',
            ], 400);
        }

        try {
            $zones = $main->getRoute53Domains();
        } catch (\Aws\Exception\AwsException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'AWS error: '.$e->getAwsErrorMessage(),
            ], 422);
        }

        $configuredZone = $data['zone'] ?? null;
        $zoneFound = $configuredZone
            ? collect($zones)->contains(fn ($z) => $z['zone'] === $configuredZone)
            : true;

        return response()->json([
            'status' => 'success',
            'message' => sprintf(
                'Connected. %d hosted zones in account.%s',
                count($zones),
                $configuredZone && !$zoneFound ? " ⚠ Configured zone '{$configuredZone}' not found." : ''
            ),
        ]);
    }

    /**
     * Disconnect — wipes saved data and disables plugin. Destructive,
     * confirm-modal-gated on the client.
     */
    public function disconnect(Request $request)
    {
        $main = new Main();
        $record = $main->getDbRecord();
        $record->reset();

        // Flip status to inactive too — reset() only nukes data, not status.
        if ($record->isActive()) {
            $record->disable();
        }

        $request->session()->flash('alert-success', 'AWS Whitelabel disconnected.');
        return redirect()->action([self::class, 'editKey']);
    }

    private static function maskCredential(?string $value): string
    {
        if (!$value) {
            return '';
        }
        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }
        return str_repeat('•', max(0, $len - 4)).substr($value, -4);
    }
}
