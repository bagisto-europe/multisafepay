<?php

namespace Bagisto\MultiSafePay\Controllers\Shop;

use Bagisto\MultiSafePay\Payment\MultiSafePay;
use Bagisto\MultiSafePay\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Controllers\Controller;

class OnePageController extends Controller
{
    protected $multiSafepay;

    protected $orderRepository;

    protected $paymentService;

    public function __construct(
        MultiSafePay $multiSafePay,
        OrderRepository $orderRepository,
        PaymentService $paymentService,
    ) {
        $this->multiSafepay = $multiSafePay;
        $this->orderRepository = $orderRepository;
        $this->paymentService = $paymentService;
    }

    /**
     * Handle the success callback after a MultiSafepay transaction.
     *
     * This method is responsible for processing the success callback from MultiSafepay
     * after a transaction. It checks for the presence of a valid order in the session,
     * retrieves transaction details, and updates the order status and creates an invoice
     * based on the transaction status.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function success(Request $request)
    {
        if (! $order = session('order')) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $orderId = $request->transactionid;
        $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');

        $transactionId = ! empty($orderPrefix) ? explode($orderPrefix, $orderId)[1] : $orderId;

        $order = $this->orderRepository->find($transactionId);

        try {
            $this->paymentService->processPayment($order);
        } catch (\Exception $e) {
            Log::error('OnePage - Error processing payment for order id '.$transactionId.': '.$e->getMessage());
        }

        return view('shop::checkout.success', compact('order'));
    }

    /**
     * Fetches and returns available payment methods from MultiSafepay.
     *
     * This method retrieves the available payment methods from MultiSafepay
     * using the configured MultiSafepay service instance and returns them
     * as a JSON response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function showPaymentMethods()
    {
        $data = collect($this->multiSafepay->getAvailablePaymentMethods());

        return response()->json($data);
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
                        'payment' => request()->all(),
                    ],
                ],
            ]);
        }

        return response()->json(['status' => true]);
    }
}
