<?php

namespace Acelle\AwsWhitelabel;

use Illuminate\Support\ServiceProvider;

class AwsWhitelabelProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
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
