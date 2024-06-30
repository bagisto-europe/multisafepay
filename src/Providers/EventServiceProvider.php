<?php

namespace Bagisto\MultiSafePay\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'sales.refund.save.after' => [
            'Bagisto\MultiSafePay\Listeners\MultiSafePayListener@afterRefundGenerated',
        ],
        'sales.shipment.save.after' => [
            'Bagisto\MultiSafePay\Listeners\MultiSafePayListener@afterOrderShipped',
        ],
    ];
}
