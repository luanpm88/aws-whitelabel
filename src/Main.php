<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Acelle\Model\Plugin as PluginModel;
use Aws\Route53\Route53Client;
use Aws\Route53Domains\Route53DomainsClient;
use Acelle\Library\Facades\Plugin;

class Main
{
    const NAME = 'acelle/aws-whitelabel';
    protected $data;

    public function __construct()
    {
        $name = self::NAME;
        $record = PluginModel::where('name', $name)->first();
        if (is_null($record)) {
            throw new \Exception("Plugin record for '{$name}' not found");
        }
        $this->data = $record->getData();
    }

    public function registerHooks()
    {
        // Register hooks
        Plugin::registerHook('filter_aws_ses_dns_records', function (&$identity, &$dkims, &$spf) {
            $this->removeAmazonSesBrand($identity, $dkims, $spf);
        });

        // Register hooks
        Plugin::registerHook('generate_plugin_setting_url_for_'.self::NAME, function (&$url) {
            $url = action('\Acelle\Plugin\AwsWhitelabel\Controllers\MainController@index');
        });

        // Register hooks
        Plugin::registerHook('generate_big_notice_for_sending_server', function ($server) {
            return "<strong> This is {$server->name} </strong>";
        });

        Plugin::registerHook('activate_plugin_'.self::NAME, function () {
            return true; // or throw an exception
        });

        Plugin::registerHook('deactivate_plugin_'.self::NAME, function () {
            return true; // or throw an exception
        });

        Plugin::registerHook('delete_plugin_'.self::NAME, function () {
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

    public function createCnameRecords($server, $domain, $tokens)
    {
        foreach ($tokens as $subname) {
            $this->changeResourceRecordSets($subname, $domain);
        }
    }

    public function getRoute53Client($server)
    {
        $client = Route53Client::factory(array(
            'credentials' => array(
                'key' => trim($server->aws_access_key_id),
                'secret' => trim($server->aws_secret_access_key),
            ),
            'region' => 'us-east-1',
            'version' => '2013-04-01',
        ));

        return $client;
    }

    /*
    public function getRoute53DomainClient($server)
    {
        $client = Route53DomainsClient::factory(array(
            'credentials' => array(
                'key' => trim($server->aws_access_key_id),
                'secret' => trim($server->aws_secret_access_key),
            ),
            'region' => $server->aws_region,
            'version' => '2014-05-15',
        ));

        return $client;
    }
    */

    private function changeResourceRecordSets($subname, $domain)
    {
        // foo.example.com. CNAME foo.amazon.dkim.amazonses.com
        $name = "{$subname}.dkim.{$domain}.";
        $value = "{$subname}.dkim.amazonses.com";
        $hostedZoneId = 'Z01341112AVEFK3WADVQQ';
        $result = $this->getRoute53Client()->changeResourceRecordSets([
            'HostedZoneId' => $hostedZoneId,
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

    public function getRoute53Domains($server)
    {
        $results = $this->getRoute53Client($server)->listHostedZones();
        
        if (!isset($results['HostedZones'])) {
            return [];
        }

        return array_map(
            function ($e) {
            return [
                    'id' => str_replace('/hostedzone/', '', $e['Id']),
                    'name' => $e['Name']
                ];
        },
            $results['HostedZones']
        );
    }
}
