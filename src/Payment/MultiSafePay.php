<?php

namespace Bagisto\MultiSafePay\Payment;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use MultiSafepay\Sdk;
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
    private $apiKey;

    /**
     * Flag indicating if sandbox mode is enabled.
     *
     * @var bool
     */
    private $sandbox;

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
    public function __construct(OrderRepository $orderRepository) {
        $this->apiKey = core()->getConfigData('sales.paymentmethods.multisafepay.apikey');

        $this->orderRepository = $orderRepository;

        $this->sandbox = core()->getConfigData('sales.paymentmethods.multisafepay.sandbox');
    }

    /**
     * Get the version number from composer.json.
     *
     * @return string The version number.
     */
    public function getVersion(): string {
        
        $composerJsonPath = dirname(dirname(__DIR__)) . '/composer.json';

        if (file_exists($composerJsonPath)) {
            $composerJson = file_get_contents($composerJsonPath);
            $composerData = json_decode($composerJson, true);
            
            if (isset($composerData['version'])) {
                return $composerData['version'];
            }
        }
    }

    /**
     * Return all available payment methods from MultiSafePay
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array {
        $multiSafepaySdk = new Sdk($this->apiKey, $this->sandbox);

        $paymentMethods = $multiSafepaySdk->getPaymentMethodManager()->getPaymentMethods();

        return $paymentMethods;
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

            $orderId = $order->increment_id;
            session(['order' => $order]);

            $multiSafepaySdk = new Sdk($this->apiKey, $this->sandbox);

            $description = '#' . $orderId;
            
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
                ->addPhoneNumber(new PhoneNumber($order->addresses["0"]->phone))
                ->addLocale('nl_NL');
                
            $pluginDetails = (new PluginDetails())
                ->addPartner('Bagisto Europe')
                ->addApplicationName('Bagisto')
                ->addApplicationVersion(core()->version())
                ->addPluginVersion($this->getVersion());
            
            $paymentOptions = (new PaymentOptions())
                //->addNotificationUrl(route('multisafepay.webhook'))
                ->addRedirectUrl(route('shop.checkout.success'))
                ->addCancelUrl(route('shop.checkout.success'))
                ->addCloseWindow(true);
            
            $orderRequest = (new OrderRequest())
                ->addType('redirect')
                ->addOrderId($orderId)
                ->addDescriptionText($description)
                ->addMoney($amount)
                ->addCustomer($customer)
                ->addDelivery($customer)
                ->addPluginDetails($pluginDetails)
                ->addPaymentOptions($paymentOptions);

            $transactionManager = $multiSafepaySdk->getTransactionManager()->create($orderRequest);

            return $transactionManager->getPaymentUrl();
        }
    }
}