<?php

namespace Bagisto\MultiSafePay\Controllers;

use Bagisto\MultiSafePay\Payment\MultiSafePay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

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
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        Event::dispatch('checkout.load.index');

        /**
         * If guest checkout is not allowed then redirect back to the cart page
         */
        if (
            !auth()->guard('customer')->check()
            && !core()->getConfigData('catalog.products.guest_checkout.allow_guest_checkout')
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        /**
         * If user is suspended then redirect back to the cart page
         */
        if (auth()->guard('customer')->user()?->is_suspended) {
            session()->flash('warning', trans('shop::app.checkout.cart.suspended-account-message'));

            return redirect()->route('shop.checkout.cart.index');
        }

        /**
         * If cart has errors then redirect back to the cart page
         */
        if (Cart::hasError()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        /**
         * If cart is has downloadable items and customer is not logged in
         * then redirect back to the cart page
         */
        if (
            !auth()->guard('customer')->check()
            && ($cart->hasDownloadableItems()
                || !$cart->hasGuestCheckoutItems()
            )
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        /**
         * If cart minimum order amount is not satisfied then redirect back to the cart page
         */
        $minimumOrderAmount = (float) core()->getConfigData('sales.order_settings.minimum_order.minimum_order_amount') ?: 0;

        if (!$cart->checkMinimumOrder()) {
            session()->flash('warning', trans('shop::app.checkout.cart.minimum-order-message', [
                'amount' => core()->currency($minimumOrderAmount)
            ]));

            return redirect()->back();
        }

        /**
         * Get all payment methods from MultiSafePay
         */
        $multisafepayPayMethods = collect($this->multiSafepay->getAvailablePaymentMethods());

        return view('multisafepay::checkout.onepage.index', compact('multisafepayPayMethods'));
    }

    /**
     * Order success page.
     *
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
            $transactionId = explode($orderPrefix, $orderId)[1];
            
            $order = $this->orderRepository->find($transactionId);

            $transactionData = $this->multiSafepay->getPaymentStatusForOrder($orderId);
            $status = $transactionData->getStatus();

            if ($status === 'completed') {
                if ($order->status = 'pending') {
                    $order->status = 'processing';
                }

                if ($order->canInvoice()) {
                    request()->merge([ 'can_create_transaction' => 1 ]);
                    $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order));
                }
            }
        }

        return view('shop::checkout.success', compact('order'));
    }

    /**
     * Prepare invoice data from order.
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
}
