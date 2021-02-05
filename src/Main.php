<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Acelle\Model\Plugin as PluginModel;
use Aws\Route53\Route53Client;
use Aws\Route53Domains\Route53DomainsClient;
use Acelle\Library\Facades\Hook;

class Main
{
    const NAME = 'acelle/aws-whitelabel';
    protected $data;

    public function __construct()
    {
        //
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

        // Hook::register('activate_plugin_'.self::NAME, function () {
        // execute ListHostedZones as a test
        // $this->getRoute53Domains();
        // });

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

    public function createCnameRecords($server, $domain, $tokens)
    {
        foreach ($tokens as $subname) {
            $this->changeResourceRecordSets($subname, $domain);
        }
    }

    public function getRoute53Client()
    {
        $data = $this->getDbRecord()->getData();
        $client = Route53Client::factory(array(
            'credentials' => array(
                'key' => $data['aws_key'],
                'secret' => $data['aws_secret'],
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

    public function getRoute53Domains()
    {
        $results = $this->getRoute53Client()->listHostedZones();
        
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

    public function connectAndActivate($keyId, $secret)
    {
        $record = $this->getDbRecord();
        $record->updateData([
            'aws_key' => $keyId,
            'aws_secret' => $secret,
        ]);

        $record->activate();
    }
}
