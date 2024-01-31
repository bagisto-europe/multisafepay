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
        if (isset($request->transactionid)) {
            $orderId = $request->transactionid;

            $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');
            
            $transactionId = !empty($orderPrefix) ? explode($orderPrefix, $orderId)[1] : $orderId;  

            $order = $this->orderRepository->find($transactionId);

            if ($order) {
                Log::info("MultiSafePay notification received for order id:" . $transactionId);

                $transactionData = $this->multiSafepay->getPaymentStatusForOrder($orderId);
                                
                $status = $transactionData->getStatus();
                
                if ($status === 'completed') {
                    $amount = $transactionData->getAmount();
                    $orderAmount = round($order->base_grand_total * 100);

                    if ($amount === $orderAmount) {
                        if ($order->status === 'pending') {

                            $order->status = 'processing';
                            $order->save();
                        }

                        if ($order->canInvoice()) {
                            request()->merge(['can_create_transaction' => 1]);
                            
                            $this->invoiceRepository->create($this->prepareInvoiceData($order));
                        } else {
                            $invoice = $this->invoiceRepository->findOneWhere(['order_id' => $order->id]);
    
                            if ($invoice) {
                                $invoice->state = 'paid';
                                $invoice->save();
                            }
                        }

                    }
                } else {
                    if ($order->canInvoice()) {
                        request()->merge(['can_create_transaction' => 1]);

                        $orderStatus = $order->status !== 'pending' ? $order->status : 'pending';
                        
                        $this->invoiceRepository->create($this->prepareInvoiceData($order), 'pending', $orderStatus);
                    }
                }
                
                return response('OK', 200);
            } else {
                return response('Order not found', 400);
            }
        } else {
            return response('Invalid notification', 400);
        }
    }

    /**
     * Prepares invoice data
     *
     * @return array
     */
    public function prepareInvoiceData($order)
    {
        $invoiceData = [
            "order_id" => $order->id
        ];

        foreach ($order->items as $item) {
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
