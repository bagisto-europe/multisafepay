<?php

namespace Bagisto\MultiSafePay\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
            dirname(__DIR__).'/Config/paymentmethods.php', 'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php', 'core'
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

        $this->loadRoutesFrom(dirname(__DIR__).'/Routes/api-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'multisafepay');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'multisafepay');

        $this->app->bind('Webkul\Core\Core', 'Bagisto\MultiSafePay\Core');

        Event::listen('bagisto.shop.customers.account.orders.reorder_button.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('multisafepay::customers.account.orders.pay-button');
        });

        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../Resources/views/shop' => $this->app->resourcePath('themes/default/views'),
                __DIR__.'/../Resources/views/admin' => $this->app->resourcePath('admin-themes/default/views'),
            ], 'multisafepay');
        }
    }
}
