{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.before') !!}

<v-payment-methods
    :methods="paymentMethods"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <x-shop::shimmer.checkout.onepage.payment-method />
</v-payment-methods>

{!! view_render_event('bagisto.shop.checkout.onepage.payment_methods.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-payment-methods-template"
    >
        <div class="mb-7 max-md:last:!mb-0">
            <template v-if="! methods">
                <!-- Payment Method shimmer Effect -->
                <x-shop::shimmer.checkout.onepage.payment-method />
            </template>
    
            <template v-else>
                {!! view_render_event('bagisto.shop.checkout.onepage.payment_method.accordion.before') !!}

                <!-- Accordion Blade Component -->
                <x-shop::accordion class="!border-b-0 max-md:rounded-xl max-md:!border-none max-md:!bg-gray-100">
                    <!-- Accordion Blade Component Header -->
                    <x-slot:header class="!p-0 max-md:!p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-lg">
                                @lang('shop::app.checkout.onepage.payment.payment-method')
                            </h2>
                        </div>
                    </x-slot>
    
                    <!-- Accordion Blade Component Content -->
                    <x-slot:content class="mt-8 !p-0 max-md:mt-0 max-md:border max-md:!p-4">
                        <div class="flex flex-wrap gap-7 max-md:gap-4 max-sm:gap-2.5">
                            <template v-for="(payment, index) in methods">
                                {!! view_render_event('bagisto.shop.checkout.payment-method.before') !!}

                                <template v-if="payment.method === 'multisafepay'">
                                    <template v-for="(paymentMethod, index) in multiSafePaymentMethods">
                                        <div class="relative cursor-pointer max-md:max-w-full max-md:flex-auto">
                                            <input 
                                                type="radio" 
                                                name="payment[method]" 
                                                :value="`${payment.method} - ${paymentMethod.id}`"
                                                :id="'paymentMethod_' + paymentMethod.id"
                                                class="peer hidden"
                                                @change="store({
                                                    ...payment,
                                                    ...{
                                                        payment_method: paymentMethod.id,
                                                        payment_method_title: paymentMethod.name
                                                    }
                                                })"
                                            >
                
                                            <label 
                                                :for="'paymentMethod_' + paymentMethod.id"
                                                class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                            >
                                            </label>

                                            <label 
                                                :for="'paymentMethod_' + paymentMethod.id"
                                                class="block w-[190px] cursor-pointer rounded-xl border border-zinc-200 p-5 max-md:flex max-md:w-full max-md:gap-2.5"
                                            >
                                                {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.before') !!}

                                                <img
                                                    class="max-h-11 max-w-14"
                                                    :src="paymentMethod.image"
                                                    width="55"
                                                    height="55"
                                                    :alt="paymentMethod.method_title"
                                                    :title="paymentMethod.method_title"
                                                />

                                                {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.after') !!}

                                                <div>
                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.before') !!}

                                                    <p class="mt-1.5 text-sm font-semibold max-md:mt-1 max-sm:mt-0">
                                                        @{{ paymentMethod.method_title }}
                                                    </p>
                                                    
                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.after') !!}

                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.before') !!}

                                                    <p class="mt-2.5 text-xs font-medium max-md:mt-1 max-sm:mt-0">
                                                        @{{ paymentMethod.description }}
                                                    </p> 

                                                    {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.after') !!}
                
                                                </div>
                                            </label>
                                        </div>
                                    </template>
                                </template>

                                <div v-else class="relative cursor-pointer max-md:max-w-full max-md:flex-auto">
                                    <input 
                                        type="radio" 
                                        name="payment[method]" 
                                        :value="payment.payment"
                                        :id="payment.method"
                                        class="peer hidden"
                                        @change="store(payment)"
                                    >
        
                                    <label 
                                        :for="payment.method" 
                                        class="icon-radio-unselect peer-checked:icon-radio-select absolute top-5 cursor-pointer text-2xl text-navyBlue ltr:right-5 rtl:left-5"
                                    >
                                    </label>

                                    <label 
                                        :for="payment.method" 
                                        class="block w-[190px] cursor-pointer rounded-xl border border-zinc-200 p-5 max-md:flex max-md:w-full max-md:gap-2.5"
                                    >
                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.before') !!}

                                        <img
                                            class="max-h-11 max-w-14"
                                            :src="payment.image"
                                            width="55"
                                            height="55"
                                            :alt="payment.method_title"
                                            :title="payment.method_title"
                                        />

                                        {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.image.after') !!}

                                        <div>
                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.before') !!}

                                            <p class="mt-1.5 text-sm font-semibold max-md:mt-1 max-sm:mt-0">
                                                @{{ payment.method_title }}
                                            </p>
                                            
                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.title.after') !!}

                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.before') !!}

                                            <p class="mt-2.5 text-xs font-medium max-md:mt-1 max-sm:mt-0">
                                                @{{ payment.description }}
                                            </p> 

                                            {!! view_render_event('bagisto.shop.checkout.onepage.payment-method.description.after') !!}
        
                                        </div>
                                    </label>
                                </div>

                                {!! view_render_event('bagisto.shop.checkout.payment-method.after') !!}

                                <!-- Todo implement the additionalDetails -->
                                {{-- \Webkul\Payment\Payment::getAdditionalDetails($payment['method'] --}}
                            </template>
                        </div>
                    </x-slot>
                </x-shop::accordion>

                {!! view_render_event('bagisto.shop.checkout.onepage.index.payment_method.accordion.before') !!}
            </template>
        </div>
    </script>

    <script type="module">
        app.component('v-payment-methods', {
            template: '#v-payment-methods-template',

            props: {
                methods: {
                    type: Object,
                    required: true,
                    default: () => null,
                },
            },

            emits: ['processing', 'processed'],

            data: function () {
                return {
                    multiSafePaymentMethods: []
                }
            },

            created() {
                this.getMultiSafePaymentMethods();
            },

            methods: {
                store(selectedMethod) {
                    this.$emit('processing', 'review');

                    this.$axios.post("{{ route('shop.checkout.onepage.payment_methods.store') }}", {
                            payment: selectedMethod
                        })
                        .then(response => {
                            this.$axios.post("{{ route('shop.checkout.onepage.multipay') }}", {
                                payment_method: selectedMethod.payment_method,
                                payment_method_title: selectedMethod.payment_method_title
                            }).then(result => {
                                this.$emit('processed', response.data.cart);
    
                                // Used in mobile view. 
                                if (window.innerWidth <= 768) {
                                    window.scrollTo({
                                        top: document.body.scrollHeight,
                                        behavior: 'smooth'
                                    });
                                }
                            });
                        })
                        .catch(error => {
                            this.$emit('processing', 'payment');

                            if (error.response.data.redirect_url) {
                                window.location.href = error.response.data.redirect_url;
                            }
                        });
                },

                getMultiSafePaymentMethods() {
                    this.$axios.get("{{ route('multisafepay.payment_methods')}}")
                    .then(response => {
                        this.multiSafePaymentMethods = response.data;
                    })
                    .catch(error => console.log(error));
                }
            },
        });
    </script>
@endPushOnce
