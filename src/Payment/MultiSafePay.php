<?php

namespace Bagisto\MultiSafePay\Payment;

use MultiSafepay\Sdk;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\ValueObject\Customer\Country;
use MultiSafepay\ValueObject\Customer\Address;
use MultiSafepay\ValueObject\Customer\PhoneNumber;
use MultiSafepay\ValueObject\Customer\EmailAddress;

use MultiSafepay\ValueObject\Money;

use MultiSafepay\Api\Transactions\OrderRequest\Arguments\CustomerDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PluginDetails;
use MultiSafepay\Api\Transactions\OrderRequest\Arguments\PaymentOptions;
use MultiSafepay\Api\Transactions\OrderRequest;

use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Payment\Payment;
use Webkul\Sales\Repositories\OrderRepository;

class MultiSafePay extends Payment
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'multisafepay';

    /**
     * API key for MultiSafepay.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Flag indicating if production mode is enabled.
     *
     * @var bool
     */
    protected $productionMode;

    /**
     * OrderRepository object.
     *
     * @var array
     */
    protected $orderRepository;

    /**
     * Create a new instance.
     *
     * @param OrderRepository $orderRepository The order repository instance.
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->apiKey = core()->getConfigData('sales.payment_methods.multisafepay.apikey');

        $this->orderRepository = $orderRepository;

        $this->productionMode = core()->getConfigData('sales.payment_methods.multisafepay.production');
    }

    /**
     * Return all available payment methods from MultiSafePay
     *
     * @return array
     */
    public function getAvailablePaymentMethods()
    {
        $multiSafepaySdk = new Sdk($this->apiKey, $this->productionMode);

        $paymentMethods = $multiSafepaySdk->getPaymentMethodManager()->getPaymentMethods();

        $result = [];

        foreach ($paymentMethods as $paymentMethod) {
            $result[] = [
                'id' => $paymentMethod->getId(),
                'name' => $paymentMethod->getName(),
                'logo' => $paymentMethod-> getLargeIconUrl(),
            ];
        }

        return $result;
    }

    /**
     * Get the payment status for a specific order ID.
     *
     * @param int $orderId The ID of the order for which to retrieve the payment status.
     *
     * @return \MultiSafepay\Api\Transactions\Transaction The payment transaction object.
     */
    public function getPaymentStatusForOrder($orderId)
    {
        $multiSafepaySdk = new Sdk($this->apiKey, $this->productionMode);
        $transaction = $multiSafepaySdk->getTransactionManager()->get($orderId);

        return $transaction;
    }

    /**
     * Get the redirect URL for MultiSafepay payment.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        if ($this->apiKey) {
            $cart = $this->getCart();
            $billingAddress = $cart->billing_address;

            $order = $this->orderRepository->create(Cart::prepareDataForOrder());

            if ($order) {
                session(['order' => $order]);

                $orderId = $order->increment_id;
                $randomOrderId = $orderId;

                $multiSafepaySdk = new Sdk($this->apiKey, $this->productionMode ?? false);

                $description = $order->channel_name . ' - Order ID #' . $orderId;

                $amount = new Money(round($cart->sub_total * 100), $cart->cart_currency_code);

                $address = (new Address())
                    ->addStreetName($billingAddress->address1)
                    ->addZipCode($billingAddress->postcode)
                    ->addCity($billingAddress->city)
                    ->addState($billingAddress->state)
                    ->addCountry(new Country($billingAddress->country));

                $customer = (new CustomerDetails())
                    ->addFirstName($billingAddress->first_name)
                    ->addLastName($billingAddress->last_name)
                    ->addAddress($address)
                    ->addEmailAddress(new EmailAddress($order->customer_email))
                    ->addPhoneNumber(new PhoneNumber($order->addresses["0"]->phone));
                //->addLocale('nl_NL');

                $pluginDetails = (new PluginDetails())
                    ->addPartner('Bagisto Europe')
                    ->addApplicationName('Bagisto')
                    ->addApplicationVersion(core()->version())
                    ->addPluginVersion($this->getPluginVersion());

                $paymentOptions = (new PaymentOptions())
                    //->addNotificationUrl(route('multisafepay.webhook'))
                    ->addRedirectUrl(route('shop.checkout.onepage.success'))
                    ->addCancelUrl(route('shop.checkout.onepage.success'))
                    ->addCloseWindow(true);

                $selectedGateway = '';
                $orderItemAdditional = $order->items->first()->additional;
                if (isset($orderItemAdditional['payment'])) {
                    $selectedGateway = $orderItemAdditional['payment']['payment_method'];
                    $orderPayment = $order->payment;
                    $orderPayment->update([
                        'additional' => array_merge($orderPayment->additional ?? [], ['payment' => $orderItemAdditional['payment']])
                    ]);
                }

                $orderRequest = (new OrderRequest())
                    ->addType('redirect')
                    ->addOrderId($randomOrderId)
                    ->addDescriptionText($description)
                    ->addMoney($amount)
                    ->addGatewayCode($selectedGateway)
                    ->addCustomer($customer)
                    ->addDelivery($customer)
                    ->addPluginDetails($pluginDetails)
                    ->addPaymentOptions($paymentOptions);

                Cart::deActivateCart();

                $transactionManager = $multiSafepaySdk->getTransactionManager()->create($orderRequest);

                return $transactionManager->getPaymentUrl();
            }
        }
    }

    /**
     * Get the version number of the Bagisto MultiSafePay package.
     *
     * @return string The version number.
     */
    public function getPluginVersion()
    {
        $manifestPath = dirname(__DIR__) . '/Resources/manifest.php';
        $manifest = include $manifestPath;
        $version = $manifest['version'];

        return $version;
    }
}
