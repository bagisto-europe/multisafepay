<?php

namespace Bagisto\MultiSafePay\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use MultiSafepay\Sdk;

use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

use Webkul\Shop\Http\Controllers\Controller;


class OnePageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Sales\Repositories\OrderRepository  $orderRepository
     * @return void
     */
    public function __construct(
        protected InvoiceRepository $invoiceRepository, 
        protected OrderRepository $orderRepository,
    )
    {

    }

    /**
     * Order success page.
     *
     * @return \Illuminate\Http\Response
     */
    public function success(Request $request)
    {
        $transactionid = $request->transactionid;

        $apiKey  = core()->getConfigData('sales.payment_methods.multisafepay.apikey');
        $production = core()->getConfigData('sales.payment_methods.multisafepay.production');

        $multiSafepaySdk = new Sdk($apiKey, $production);      
        $order = $this->orderRepository->find($transactionid);

        $transaction = $multiSafepaySdk->getTransactionManager()->get($order->id);
        $status = $transaction->getStatus();

        if ($status === 'completed' && $order->canInvoice()) {
            $order->status = 'processing';

            $this->invoiceRepository->create($this->prepareInvoiceData($order));
        }
       
        return view('shop::checkout.success', compact('order'));
    }

    /**
     * Prepares order's invoice data for creation.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array
     */
    protected function prepareInvoiceData($order)
    {
        $invoiceData = ['order_id' => $order->id];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }
}