<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Acelle\Model\Plugin as PluginModel;
use Aws\Route53\Route53Client;
use Aws\Route53Domains\Route53DomainsClient;
use Acelle\Library\Facades\Hook;
use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Main
{
    const NAME = 'acelle/aws-whitelabel';

    public function __construct()
    {
        //
    }

    public function logger()
    {
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");

        $logfile = storage_path('logs/' . php_sapi_name() . '/aws-whitelabel.log');
        $stream = new RotatingFileHandler($logfile, 0, Logger::DEBUG);
        $stream->setFormatter($formatter);

        $pid = getmypid();
        $logger = new Logger($pid);
        $logger->pushHandler($stream);

        return $logger;
    }

    public function getDbRecord()
    {
        return PluginModel::where('name', self::NAME)->first();
    }

    public function registerHooks()
    {
        // Register hooks
        Hook::register('filter_aws_ses_dns_records', function (&$identity, &$dkims, &$spf) {
            $this->removeAmazonSesBrand($identity, $dkims, $spf);
        });

        // Register hooks
        Hook::register('generate_big_notice_for_sending_server', function ($server) {
            return view('awswhitelabel::notification', [
                'server' => $server,
            ]);
        });

        Hook::register('after_verify_dkim_against_aws_ses', function ($domain, $tokens) {
            $this->logger()->info("Start generating proxy DNS for {$domain}");
            foreach ($tokens as $subname) {
                $this->logger()->info("- {$subname} for {$domain}");
                $this->changeResourceRecordSets($subname, $domain);
                $this->logger()->info("==> {$subname} for {$domain} DONE");
            }
        });

        Hook::register('activate_plugin_'.self::NAME, function () {
            // Run this method as a test
            $this->getRoute53Domains();
        });

        Hook::register('deactivate_plugin_'.self::NAME, function () {
            return true; // or throw an exception
        });

        Hook::register('delete_plugin_'.self::NAME, function () {
            return true; // or throw an exception
        });
    }

    public function removeAmazonSesBrand(&$identity, &$dkims, &$spf)
    {
        $domain = 'acelle.link';
        $identity = null;
        for ($i = 0; $i < sizeof($dkims); $i += 1) {
            $dkim = $dkims[$i];
            $dkim['value'] = str_replace('.dkim.amazonses.com', ".dkim.{$domain}", $dkim['value']);
            $dkims[$i] = $dkim;
        }
        $spf = null;
    }

    public function getRoute53Client()
    {
        $data = $this->getDbRecord()->getData();

        if (!array_key_exists('aws_key', $data) || !array_key_exists('aws_secret', $data)) {
            throw new \Exception('Plugin AWS Whitelabel not configured yet');
        }

        $client = self::initRoute53Client($data['aws_key'], $data['aws_secret']);

        return $client;
    }

    public static function initRoute53Client($key, $secret)
    {
        $client = Route53Client::factory(array(
            'credentials' => array(
                'key' => $key,
                'secret' => $secret,
            ),
            'region' => 'us-east-1',
            'version' => '2013-04-01',
        ));

        return $client;
    }

    private function changeResourceRecordSets($subname)
    {
        $data = $this->getDbRecord()->getData();
        $brandDomain = $data['domain'];
        $hostedzone = $data['zone'];

        // foo.example.com. CNAME foo.amazon.dkim.amazonses.com
        $name = "{$subname}.dkim.{$brandDomain}";
        $value = "{$subname}.dkim.amazonses.com";

        $result = $this->getRoute53Client()->changeResourceRecordSets([
            'HostedZoneId' => $hostedzone,
            'ChangeBatch' => array(
                'Comment' => 'string',
                'Changes' => array(
                    array(
                        'Action' => 'UPSERT',
                        'ResourceRecordSet' => array(
                            'Name' => $name,
                            'Type' => 'CNAME',
                            'TTL' => 600,
                            'ResourceRecords' => array(
                                array(
                                    'Value' => $value,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ]);

        return $result;
    }

    public function getRoute53Domains()
    {
        $results = $this->getRoute53Client()->listHostedZones();
        
        if (!isset($results['HostedZones'])) {
            return [];
        }

        return array_map(
            function ($e) {
                $hostedZone = str_replace('/hostedzone/', '', $e['Id']);
                $name = $e['Name'];
                return [
                    'zone' => $hostedZone,
                    'name' => $name,
                ];
            },
            $results['HostedZones']
        );
    }

    public function testRoute53Connection($keyId, $secret)
    {
        $client = self::initRoute53Client($keyId, $secret);
        $client->listHostedZones();
    }

    public function connectAndSave($keyId, $secret)
    {
        // Test or throw exception
        $this->testRoute53Connection($keyId, $secret);

        // Test OK, proceed
        $record = $this->getDbRecord();
        $record->updateData([
            'aws_key' => $keyId,
            'aws_secret' => $secret,
        ]);
    }

    public function updateDomain($domainAndZone)
    {
        list($domain, $zone) = explode('|', $domainAndZone);
        $record = $this->getDbRecord();
        $record->updateData([
            'domain' => $domain,
            'zone' => $zone,
        ]);
    }

    public function activate()
    {
        $record = $this->getDbRecord();
        $record->activate();
    }
}
