<?php

namespace Bagisto\MultiSafePay\Controllers\API;

use Bagisto\MultiSafePay\Payment\MultiSafePay;
use Bagisto\MultiSafePay\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use MultiSafepay\Util\Notification;
use Webkul\Sales\Repositories\OrderRepository;

class WebhookController extends Controller
{
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
     * Payment service object
     *
     * @var \Bagisto\MultiSafePay\Services\PaymentService
     */
    protected $paymentService;

    /**
     * Order repository object
     *
     * @var \Webkul\Sales\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Sales\Repositories\OrderTransactionRepository  $orderTransactionRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        MultiSafePay $multiSafepay,
        PaymentService $paymentService
    ) {
        $this->multiSafepay = $multiSafepay;
        $this->paymentService = $paymentService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Handle MultiSafepay webhook notifications.
     *
     * @param  Request  $request  The HTTP request object.
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        $isValid = $this->validateNotification($request);

        if (! $isValid) {
            return response('Invalid notification or authentication failed', 400);
        }

        $orderId = $request->transactionid;
        $orderPrefix = core()->getConfigData('sales.payment_methods.multisafepay.prefix');
        $transactionId = ! empty($orderPrefix) ? explode($orderPrefix, $orderId)[1] : $orderId;

        $order = $this->orderRepository->find($transactionId);

        if (! $order) {
            return response('Order not found', 404);
        }

        try {
            $this->paymentService->processPayment($order);
        } catch (\Exception $e) {
            Log::error('MultiSafepay - Error processing payment for order id '.$transactionId.': '.$e->getMessage());

            return response('Error processing payment', 500);
        }

        return response('OK', 200);
    }

    /**
     * Validate the incoming notification using MultiSafepay's Notification class.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request object.
     * @return bool
     */
    protected function validateNotification(Request $request): bool
    {
        $authHeader = $request->header('Authorization');
        $apiKey = core()->getConfigData('sales.payment_methods.multisafepay.apikey');

        try {
            return Notification::verifyNotification(
                $request->getContent(),
                $authHeader,
                $apiKey
            );
        } catch (\Exception $e) {
            Log::error('MultiSafepay - Error validating notification: '.$e->getMessage());
            return false;
        }
    }
}
