<?php

return [
    [
        'key'    => 'sales.payment_methods.multisafepay',
        'name'   => 'multisafepay::app.system.title',
        'info'   => 'multisafepay::app.system.info',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'production',
                'title'         => 'multisafepay::app.system.production',
                'type'          => 'boolean',
                'depends'       => 'active:1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'apikey',
                'title'         => 'multisafepay::app.system.api-key',
                'type'          => 'text',
                'depends'       => 'active:1',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'prefix',
                'title'         => 'multisafepay::app.system.order-id-prefix',
                'type'          => 'text',
                'depends'       => 'active:1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'display_cart_items',
                'title'         => 'multisafepay::app.system.display_cart_items',
                'info'          => 'multisafepay::app.system.display_cart_items_info',
                'type'          => 'boolean',
                'depends'       => 'active:1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
        ],
    ],
];
