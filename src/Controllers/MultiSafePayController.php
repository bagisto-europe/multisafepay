<?php

namespace Bagisto\MultiSafePay\Controllers;

use Bagisto\MultiSafePay\Payment\MultiSafePay;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class MultiSafePayController extends Controller
{

    /**
     * apiKey object
     *
     * @var object
     */
    private $apiKey;

    /**
     * Production object
     *
     * @var object
     */
    private $productionMode;


    /**
     * Order object
     *
     * @var object
     */
    protected $order;


    /**
     * MultiSafePay object
     *
     * @var object
     */
    protected $multiSafepay;


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
        MultiSafePay $multiSafepay
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->multiSafepay = $multiSafepay;

        $this->apiKey  = core()->getConfigData('sales.payment_methods.multisafepay.apikey');
        $this->productionMode = core()->getConfigData('sales.payment_methods.multisafepay.production');
    }

    /**
     * Handle MultiSafepay webhook notifications.
     *
     * @param Request $request The HTTP request object.
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        Log::info("MultiSafePay webhook received");

        if (isset($request->transactionid)) {
            $orderId = $request->transactionid;

            $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');
            
            if (isset($orderPrefix)) {
                $transactionId = explode($orderPrefix, $orderId)[1];
            } else {
                $transactionId = $request->transactionid;
            }

            $order = $this->orderRepository->find($transactionId);

            $transactionData = $this->multiSafepay->getPaymentStatusForOrder($orderId);
            $status = $transactionData->getStatus();

            if ($status === 'completed') {
                if ($order->status = 'pending') {
                    $order->status = 'processing';
                }

                if ($order->canInvoice()) {
                    request()->merge(['can_create_transaction' => 1]);
                    $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order));
                }
            }

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
        $data = collect($this->multiSafepay->getAvailablePaymentMethods());

        return response()->json($data);
    }
}
