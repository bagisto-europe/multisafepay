<?php

namespace Bagisto\MultiSafePay;

use Webkul\Core\Core as WebkulCore;

class Core extends WebkulCore
{
    /**
     * Retrieve information from payment configuration.
     *
     * @param  string  $field
     * @param  int|string|null  $channelId
     * @param  string|null  $locale
     * @return mixed
     */
    public function getConfigData($field, $channel = null, $locale = null)
    {
        if (
            $field === "sales.payment_methods.multisafepay.title"
            && request()->id
            && (
                (
                    str_contains(request()->route()->getName(), 'orders')
                    && ($order = app('\Webkul\Sales\Repositories\OrderRepository')->find(request()->id))
                )
                || (
                    str_contains(request()->route()->getName(), 'invoices')
                    && ($invoice = app('\Webkul\Sales\Repositories\InvoiceRepository')->find(request()->id))
                    && ($order = $invoice->order)
                )
            )
            && ($additionalPaymentInfo = $order->payment->additional['payment'] ?? false)
            && $additionalPaymentInfo
        ) {
            return $additionalPaymentInfo['payment_method_title'];
        } else {
            return parent::getConfigData($field, $channel = null, $locale = null);
        }
    }
}
