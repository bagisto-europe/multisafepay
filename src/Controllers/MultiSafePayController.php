<?php

namespace Bagisto\MultiSafePay\Controllers;

use Bagisto\MultiSafePay\Payment\MultiSafePay;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use MultiSafepay\Sdk;
use Webkul\Checkout\Facades\Cart;

use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
 
class MultiSafePayController extends Controller
{
    /**
     * Order object
     *
     * @var object
     */
    protected $order;

    /**
     * Order repository instance.
     *
     * @var \Webkul\Sales\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * Order transaction repository instance.
     *
     * @var \Webkul\Sales\Repositories\OrderTransactionRepository
     */
    protected $orderTransactionRepository;

    /**
     * InvoiceRepository object
     *
     * @var \Webkul\Sales\Repositories\InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * Create a new controller instance.
     *
     * @param \Webkul\Sales\Repositories\InvoiceRepository $invoiceRepository
     * @param \Webkul\Sales\Repositories\OrderRepository $orderRepository
     * @param \Webkul\Sales\Repositories\OrderTransactionRepository $orderTransactionRepository
     */
    public function __construct(
        InvoiceRepository $invoiceRepository, 
        OrderRepository $orderRepository, 
        OrderTransactionRepository $orderTransactionRepository,
        MultiSafePay $multiSafepay
    )
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->multisafepay = $multiSafepay;

        $apiKey  = core()->getConfigData('sales.paymentmethods.multisafepay.apikey');
        $sandbox = core()->getConfigData('sales.paymentmethods.multisafepay.sandbox');
    }

    /**
     * Handle MultiSafepay webhook notifications.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\Response
     */
    public function webhook(string $orderId)
    {


        $multiSafepaySdk = new Sdk($this->apiKey, $this->$sandbox);
        $transaction = $multiSafepaySdk->getTransactionManager()->get($orderId);

        $status = $transaction->getStatus();

        $this->order = $this->orderRepository->find($orderId);

        if ($this->order) {

            if ($this->order->status = 'pending') {
                
                if ($status === 'completed') {
                    $this->order->status = 'processing';
                    
                    if ($this->order->canInvoice()) {
                        $this->invoiceRepository->create($this->prepareInvoiceData());
                    }

                } elseif ($status === 'cancelled' || $status === 'void') {
                    $this->orderRepository->cancel($transaction->getOrderId());
                }
            };

            $this->order->save();

            // Acknowledge the notification by returning a response with HTTP status code 200
            return response('OK', 200);
        } else {
            // Invalid or incomplete notification, return an error response
            return response('Invalid notification', 400);
        }
    }

    /**
     * Prepares invoice data
     *
     * @return array
    */
    public function prepareInvoiceData()
    {
        $invoiceData = [
            "order_id" => $this->order->id
        ];

        foreach ($this->order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }

    public function showPaymentMethods() 
    {
        $data = $this->multisafepay->getAvailablePaymentMethods();

        dd($data);
    }
}
