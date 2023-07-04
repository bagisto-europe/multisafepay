<?php

return [
    [
        'key'    => 'sales.paymentmethods.multisafepay',
        'name'   => 'MultiSafePay',
        'sort'   => 3,
        'fields' => [
            [
                'name'          => 'apikey',
                'title'         => 'API Key',
                'type'          => 'text',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'sandbox',
                'title'         => 'Production environment',
                'type'          => 'boolean',
                'channel_based' => true,
                'locale_based'  => false,
            ], [
                'name'          => 'active',
                'title'         => 'admin::app.admin.system.status',
                'type'          => 'boolean',
                'validation'    => 'required',
                'channel_based' => true,
                'locale_based'  => false,
            ]
        ]
    ]
];