<?php

namespace Bagisto\MultiSafePay\Listeners;

use MultiSafepay\ValueObject\Money;
use MultiSafepay\Api\Transactions\RefundRequest;

class MultiSafePayListener
{
    /**
     * After refund is created
     *
     * @param  \Webkul\Sale\Contracts\Refund  $refund
     * @return void
     */
    public function afterRefundGenerated($refund)
    {
        $apiKey = core()->getConfigData('sales.payment_methods.multisafepay.apikey');
        $isProduction = core()->getConfigData('sales.payment_methods.multisafepay.production');
        $multiSafepaySdk = new \MultiSafepay\Sdk($apiKey, $isProduction);

        $orderId = core()->getConfigData('sales.payment_methods.multisafepay.prefix') . $refund->order_id;

        try {
            $refundAmount = new Money(round($refund->grand_total * 100), $refund->order->base_currency_code);
            $transactionManager = $multiSafepaySdk->getTransactionManager();
            $transaction = $transactionManager->get($orderId);
            $response = $transactionManager->refund($transaction, (new RefundRequest())->addMoney( $refundAmount ) );
            \Log::info("MultiSafePay refund generated for order id: $orderId");
        } catch (\Exception $exception) {
            \Log::info("MultiSafePay exception while generating for order id: $orderId");
            \Log::info($exception);
        }

        return true;
    }
}
