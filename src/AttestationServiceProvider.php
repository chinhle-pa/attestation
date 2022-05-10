<?php

namespace ChinhlePa\Attestation;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use ChinhlePa\Attestation\Http\Middleware\CapitalizeTitle;
use ChinhlePa\Attestation\Http\Middleware\EnsureDevice;

class AttestationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'attestation');
        // Register the main class to use with the facade
        $this->app->singleton('attestation', function () {
            return new Attestation;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('capitalize', CapitalizeTitle::class);
        $router->aliasMiddleware('ensuredevice', EnsureDevice::class);
        
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'attestation');
        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/attestation'),
        ]);
    }
}
