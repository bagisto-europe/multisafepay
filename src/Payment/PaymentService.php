<?php

namespace Bagisto\MultiSafePay\Payment;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class PaymentService
{
    protected $orderRepository;

    protected $invoiceRepository;

    protected $multiSafePay;

    /**
     * PaymentService constructor.
     */
    public function __construct(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        MultiSafePay $multiSafePay
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->multiSafePay = $multiSafePay;
    }

    /**
     * Process payment for the given order.
     *
     * @return void
     */
    public function processPayment(object $order)
    {
        Log::info('MultiSafePay customer payment notification received for order id: '.$order->id);

        $transactionData = $this->multiSafePay->getPaymentStatusForOrder($order->id);

        $status = $transactionData->getStatus();

        if ($status === 'completed') {
            $amount = $transactionData->getAmount();
            $orderAmount = round($order->base_grand_total * 100);

            if ($amount === $orderAmount) {
                $this->updateOrderStatus($order, $transactionData);
                $this->handleInvoice($order);
            }
        }

        $order->payment->method = 'multisafepay';
        $order->payment->method_title = $transactionData->getData()['payment_details']['type'];
        $order->payment->save();
    }

    /**
     * Update the status of the order.
     *
     * @param  mixed  $order  The order object.
     */
    protected function updateOrderStatus($order): void
    {
        if ($order->status === 'pending') {
            $order->status = 'processing';
        }

        $order->save();
    }

    /**
     * Handle invoice creation or update for the order.
     *
     * @param  mixed  $order
     */
    protected function handleInvoice($order): void
    {
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

    /**
     * Prepares invoice data.
     *
     * @param  mixed  $order  The order object.
     * @return array The prepared invoice data.
     */
    public function prepareInvoiceData($order): array
    {
        $invoiceData = [
            'order_id' => $order->id,
            'invoice'  => [
                'items' => [],
            ],
        ];

        foreach ($order->items as $item) {
            $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
        }

        return $invoiceData;
    }
}
