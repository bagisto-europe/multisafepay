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
        if ($refund->order->payment->method !== 'multisafepay') {
            return;
        }

        $apiKey = core()->getConfigData('sales.payment_methods.multisafepay.apikey');
        $isProduction = core()->getConfigData('sales.payment_methods.multisafepay.production');
        $multiSafepaySdk = new \MultiSafepay\Sdk($apiKey, $isProduction);

        $orderId = core()->getConfigData('sales.payment_methods.multisafepay.prefix') . $refund->order_id;

        try {
            $refundAmount = new Money(round($refund->grand_total * 100), $refund->order->base_currency_code);
            $transactionManager = $multiSafepaySdk->getTransactionManager();
            $transaction = $transactionManager->get($orderId);
            $response = $transactionManager->refund($transaction, (new RefundRequest())->addMoney( $refundAmount ) );

            $orderTransactionRepository = app('\Webkul\Sales\Repositories\OrderTransactionRepository');
            $transaction = $orderTransactionRepository->findWhere([
                'order_id' => $refund->order_id
            ])->first();
            
            $orderTransactionRepository->create([
                'transaction_id' => $transaction->transaction_id,
                'status' => 'refunded',
                'amount' => $refund->grand_total,
                'type' => $transaction->type,
                'payment_method' => $transaction->payment_method,
                'invoice_id' => $transaction->invoice_id,
                'order_id' => $transaction->order_id,
            ]);
            \Log::info("MultiSafePay refund generated for order id: $orderId");
        } catch (\Exception $exception) {
            \Log::info("MultiSafePay exception while generating for order id: $orderId");
            \Log::info($exception);
        }

        return true;
    }
}
