<?php

namespace Bagisto\MultiSafePay\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class MultiSafePayServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/paymentmethods.php', 'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/system.php', 'core'
        );

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        /* loaders */
        Route::middleware('web')->group(dirname(__DIR__).'/Routes/web.php');
        
        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/api.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'multisafepay');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'multisafepay');

        $this->app->bind('Webkul\Core\Core', 'Bagisto\MultiSafePay\Core');

        $this->app->register(EventServiceProvider::class);
    }
}