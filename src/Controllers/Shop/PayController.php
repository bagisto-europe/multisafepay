<?php

namespace Bagisto\MultiSafePay\Controllers\Shop;

use Bagisto\MultiSafePay\Payment\MultiSafePay;
use Bagisto\MultiSafePay\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MultiSafepay\Api\Transactions\OrderRequest;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Sdk;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\EmailAddress;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Money;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Controllers\Controller;

class PayController extends Controller
{
    protected $orderRepository;

    protected $invoiceRepository;

    protected $customerAddressRepository;

    protected $multiSafepaySdk;

    protected $multiSafePay;

    protected $paymentService;

    public function __construct(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CustomerAddressRepository $customerAddressRepository,
        MultiSafePay $multiSafePay,
        PaymentService $paymentService,
    ) {
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->multiSafePay = $multiSafePay;
        $this->multiSafepaySdk = new Sdk(core()->getConfigData(
            'sales.payment_methods.multisafepay.apikey'),
            core()->getConfigData('sales.payment_methods.multisafepay.production')
        );

        $this->paymentService = $paymentService;
    }

    public function register($id)
    {
        $order = $this->orderRepository->findOneWhere([
            'customer_id' => auth()->guard('customer')->id(),
            'id'          => $id,
        ]);

        abort_if(! $order, 404);

        $transactionData = $this->multiSafePay->getPaymentStatusForOrder($order->id);

        if ($transactionData) {
            $status = $transactionData->getStatus();

            if ($status === 'completed') {
                session()->flash('error', trans('multisafepay::app.shop.order_already_paid'));

                return redirect()->back();
            }
        }

        $amount = new Money($order->base_grand_total * 100, $order->order_currency_code);

        $shippingAddress = $this->customerAddressRepository->findOneWhere(['customer_id' => $order->customer_id]);
        abort_if(! $shippingAddress, 404);

        $address = (new Address())
            ->addStreetName($shippingAddress->address)
            ->addZipCode($shippingAddress->postcode)
            ->addCity($shippingAddress->city)
            ->addCountry(new Country($shippingAddress->country));

        $customer = (new CustomerDetails())
            ->addFirstName($order->customer_first_name)
            ->addLastName($order->customer_last_name)
            ->addAddress($address)
            ->addEmailAddress(new EmailAddress($shippingAddress->email))
            ->addPhoneNumber(new PhoneNumber($shippingAddress->phone));

        $pluginDetails = (new PluginDetails())
            ->addApplicationName('Bagisto')
            ->addApplicationVersion(core()->version())
            ->addPluginVersion($this->multiSafePay->getPluginVersion());

        $paymentOptions = (new PaymentOptions())
            ->addNotificationUrl(route('shop.api.multisafepay.webhook'))
            ->addNotificationMethod('POST')
            ->addRedirectUrl(route('shop.customer.order.paid', $order->id))
            ->addCancelUrl(route('shop.customers.account.orders.view', $order->id))
            ->addCloseWindow(true);

        $orderRequest = (new OrderRequest())
            ->addType('redirect')
            ->addOrderId((string) $order->id)
            ->addDescriptionText($order->id)
            ->addMoney($amount)
            ->addCustomer($customer)
            ->addDelivery($customer)
            ->addPluginDetails($pluginDetails)
            ->addPaymentOptions($paymentOptions);

        $transactionManager = $this->multiSafepaySdk->getTransactionManager()->create($orderRequest);
        $paymentUrl = $transactionManager->getPaymentUrl();

        return redirect()->away($paymentUrl);
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
        if (! $request->has('transactionid')) {
            return redirect()->route('shop.customers.account.orders.index')
                ->with('error', trans('multisafepay::app.shop.invalid_transaction_id'));
        }

        $orderId = $request->transactionid;
        $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');

        $transactionId = ! empty($orderPrefix) ? explode($orderPrefix, $orderId)[1] : $orderId;

        $order = $this->orderRepository->find($transactionId);

        try {
            $this->paymentService->processPayment($order);
        } catch (\Exception $e) {
            Log::error('Error processing payment for order id '.$transactionId.': '.$e->getMessage());

            return redirect()->route('shop.customers.account.orders.index')
                ->with('error', trans('multisafepay::app.shop.payment_processing_error'));
        }

        return redirect()->route('shop.customers.account.orders.view', $order->id)
            ->with('success', trans('multisafepay::app.shop.payment_success',
                ['amount' => core()->formatPrice($order->base_grand_total)])
            );
    }
}
