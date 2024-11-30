<?php

namespace Bagisto\MultiSafePay;

use Webkul\Core\Core as WebkulCore;

class Core extends WebkulCore
{
    /**
     * Retrieve information from payment configuration.
     *
     * @param  string  $field
     * @param  int|string|null  $currentChannelCode
     * @param  string|null  $currentLocaleCode
     * @return mixed
     */
    public function getConfigData(string $field, ?string $currentChannelCode = null, ?string $currentLocaleCode = null): mixed
    {
        if (
            $field === "sales.payment_methods.multisafepay.title"
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
            return $additionalPaymentInfo['payment_method_title'];
        } else {
            return parent::getConfigData($field, $currentChannelCode = null, $currentLocaleCode = null);
        }
    }
}
