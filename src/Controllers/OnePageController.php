<?php

namespace Bagisto\MultiSafePay\Controllers;

use Bagisto\MultiSafePay\Payment\MultiSafePay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

use Webkul\Shop\Http\Controllers\Controller;

class OnePageController extends Controller
{

    protected $invoiceRepository;

    protected $orderRepository;

    protected $multiSafepay;

    public function __construct(
        InvoiceRepository $invoiceRepository,
        OrderRepository $orderRepository,
        MultiSafePay $multiSafepay
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository = $orderRepository;
        $this->multiSafepay = $multiSafepay;
    }

    /**
     * Handle the success callback after a MultiSafepay transaction.
     *
     * This method is responsible for processing the success callback from MultiSafepay
     * after a transaction. It checks for the presence of a valid order in the session,
     * retrieves transaction details, and updates the order status and creates an invoice
     * based on the transaction status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */

    public function success(Request $request)
    {
        if (!$order = session('order')) {
            return redirect()->route('shop.checkout.cart.index');
        }

        if (isset($request->transactionid)) {
            $orderId = $request->transactionid;
            $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');

            $transactionId = !empty($orderPrefix) ? explode($orderPrefix, $orderId)[1] : $orderId;

            $order = $this->orderRepository->find($transactionId);

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
        }

        return view('shop::checkout.success', compact('order'));
    }

    /**
     * Store payment information in session for MultiSafepay.
     *
     * This method is responsible for storing payment information related to MultiSafepay
     * in the session. It checks if the current payment method in the cart is MultiSafepay,
     * and if so, it updates the additional information for the first item in the cart
     * with the payment details from the request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeInSession()
    {
        $cart = Cart::getCart();
        if ($cart->payment->method === 'multisafepay') {
            $cartFirstItem = $cart->items->first();
            $cartFirstItem->update([
                'additional' => [
                    ...$cartFirstItem->additional,
                    ...[
                        'payment' => request()->all()
                    ]
                ]
            ]);
        }

        return response()->json(['status' => true]);
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
}
