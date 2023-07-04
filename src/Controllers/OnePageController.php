<?php

namespace Bagisto\MultiSafePay\Controllers;

use Illuminate\Support\Facades\Event;

use MultiSafepay\Sdk;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Http\Requests\CustomerAddressForm;
use Webkul\Customer\Repositories\CustomerRepository;

use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shipping\Facades\Shipping;

use Webkul\Shop\Http\Controllers\Controller;


class OnePageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Attribute\Repositories\OrderRepository  $orderRepository
     * @param  \Webkul\Customer\Repositories\CustomerRepository  $customerRepository
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected CustomerRepository $customerRepository
    )
    {
        parent::__construct();
    }


    /**
     * Order success page.
     *
     * @return \Illuminate\Http\Response
     */
    public function success()
    {
        $apiKey  = core()->getConfigData('sales.paymentmethods.multisafepay.apikey');
        $sandbox = core()->getConfigData('sales.paymentmethods.multisafepay.sandbox');

        $multiSafepaySdk = new Sdk($apiKey, $sandbox);
        $order = session('order');

        $transaction = $multiSafepaySdk->getTransactionManager()->get($order->id);

        $status = $transaction->getStatus();
        
        echo $status;

        dd($order);
        return view($this->_config['view'], compact('order'));
    }
}