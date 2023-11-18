<?php

namespace Bagisto\MultiSafePay\Listeners;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use MultiSafepay\ValueObject\Money;
use MultiSafepay\Api\Transactions\UpdateRequest;
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
            $response = $transactionManager->refund($transaction, (new RefundRequest())->addMoney($refundAmount));

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
            Log::info("MultiSafepay refund generated for order id: $orderId");
        } catch (\Exception $exception) {
            Log::info("MultiSafepay exception while generating refund for order id: $orderId");
            Log::info($exception);
        }

        return true;
    }

    /**
     * After order has been shipped
     *
     * @param  \Webkul\Sale\Contracts\Shipment  $shipment
     * @return void
     */
    public function afterOrderShipped($shipment)
    {
        if ($shipment->order->payment->method !== 'multisafepay') {
            return;
        }

        $apiKey = core()->getConfigData('sales.payment_methods.multisafepay.apikey');
        $isProduction = core()->getConfigData('sales.payment_methods.multisafepay.production');
        $multiSafepaySdk = new \MultiSafepay\Sdk($apiKey, $isProduction);

        $orderId = core()->getConfigData('sales.payment_methods.multisafepay.prefix') . $shipment->order_id;

        try {            
            $updateRequest = new UpdateRequest();
            $updateRequest->addId($orderId);
            $updateRequest->addStatus('shipped');

            $multiSafepaySdk->getTransactionManager()->update($orderId, $updateRequest);
        } catch (\Exception $exception) {
            Log::error("MultiSafepay exception while trying to update the status to shipped for order id: $orderId");
            Log::error($exception);
        }
    }
}
