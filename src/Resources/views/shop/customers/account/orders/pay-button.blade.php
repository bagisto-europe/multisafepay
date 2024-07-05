@if ($order->canInvoice() || $order->hasOpenInvoice()) 
    <a href="{{ route('shop.customer.order.pay', $order) }}" 
       class="secondary-button border-zinc-200 px-5 py-3 font-normal max-md:hidden">
       @lang('multisafepay::app.shop.pay-button')
    </a>
@endif