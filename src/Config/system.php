<?php

return [
    [
        'key'    => 'sales.payment_methods.multisafepay',
        'name'   => 'multisafepay::app.system.title',
        'info'   => 'multisafepay::app.system.info',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'apikey',
                'title'         => 'multisafepay::app.system.api-key',
                'type'          => 'text',
                'validation'    => 'required_if:active,1',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'prefix',
                'title'         => 'multisafepay::app.system.order-id-prefix',
                'type'          => 'text',
                'info'          => 'multisafepay::app.system.order-id-prefix',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'production',
                'title'         => 'multisafepay::app.system.production',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ],
            [
                'name'          => 'active',
                'title'         => 'admin::app.configuration.index.sales.payment-methods.status',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ]
        ]
    ]
];