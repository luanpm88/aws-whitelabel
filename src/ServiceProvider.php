<?php

namespace Acelle\Plugin\AwsWhitelabel;

use Illuminate\Support\ServiceProvider as Base;
use Acelle\Plugin\AwsWhitelabel\Main;

class ServiceProvider extends Base
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Get the Plugin Main object
        $main = new Main();

        $main->registerHooks();

        /*

        // The following is executed after
        // Plugin::load('acelle/aws-whitelabel')

        // Test hook execution
        echo 'booted';
        $identity = 1;
        $dkims = [['value' => 'u83973948392438.dkim.amazonses.com']];
        $spf = 1343;
        Plugin::executeHook('filter_aws_ses_dns_records', [ &$identity, &$dkims, &$spf ]);

        echo "$identity";
        var_dump($dkims);
        var_dump($spf);
        die;
        */

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'awswhitelabel');

        $this->publishes([
            __DIR__.'/../resources/views' => $this->app->basePath('resources/views/vendor/awswhitelabel'),
        ]);

        // lang
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'awswhitelabel');

        // routes
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // view
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'awswhitelabel');

        // assets
        $this->publishes([
            __DIR__.'/../assets' => public_path('vendor/awswhitelabel'),
        ], 'public');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
