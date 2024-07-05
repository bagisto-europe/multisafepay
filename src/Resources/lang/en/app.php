<?php

return [
    'system' => [
        'title'                   => 'MultiSafePay',
        'info'                    => 'Offers the most popular payment methods to increase your sales locally and internationally',
        'api-key'                 => 'API Key',
        'production'              => 'Production mode',
        'order-id-prefix'         => 'Order ID Prefix',
        'display_cart_items'      => 'Display shopping cart',
        'display_cart_items_info' => 'Display items from the customer\'s cart on the payment page',
    ],
    'shop' => [
        'order_already_paid'       => 'This order is already paid.',
        'pay-button'               => 'Pay now',
        'payment_success'          => 'Thank you for your payment of :amount.',
        'payment_processing_error' => 'Error processing payment.',
    ],
    'emails' => [
        'orders' => [
            'created' => [
                'payment' => "To complete your purchase, please click the link below to proceed with payment:",
                'complete-payment' => "Complete Payment",
                'already-paid' => "If you have already made the payment, please disregard this reminder.",
            ],
        ],
    ],
];
