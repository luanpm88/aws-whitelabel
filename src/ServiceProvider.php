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

        // Only register hook if plugin is active
        // However, it is better that the host application controls this
        if ($main->getDbRecord()->isActive()) {
            $main->registerHooks();
        }

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
