<?php

namespace Bagisto\MultiSafePay\Providers;

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
    public function boot()
    {
        $this->loadRoutesFrom(dirname(__DIR__) . '/Routes/web.php');

        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'multisafepay');
    }
}