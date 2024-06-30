<?php

namespace Bagisto\MultiSafePay;

use Webkul\Core\Core as WebkulCore;

class Core extends WebkulCore
{
    /**
     * Retrieve information from payment configuration.
     *
     * @param  int|string|null  $channelId
     * @param  string|null  $locale
     */
    public function getConfigData(string $field, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): mixed
    {
        if (
            $field === 'sales.payment_methods.multisafepay.title'
            && request()->id
            && (
                (
                    str_contains(request()->route()->getName(), 'invoice')
                    && ($invoice = app('\Webkul\Sales\Repositories\InvoiceRepository')->find(request()->id))
                    && ($order = $invoice->order)
                ) || (
                    str_contains(request()->route()->getName(), 'order')
                    && ($order = app('\Webkul\Sales\Repositories\OrderRepository')->find(request()->id))
                )
            )
            && ($additionalPaymentInfo = $order->payment->additional['payment'] ?? false)
            && $additionalPaymentInfo
        ) {
            return $additionalPaymentInfo['payment_method'];
        } else {
            return system_config()->getConfigData($field, $currentChannelCode, $currentLocaleCode);
        }
    }
}
