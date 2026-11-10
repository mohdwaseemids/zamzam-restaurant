@extends('layouts.site')

@section('title', 'Checkout — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('header-class', 'bg-ink')

@section('content')
@php
    $defaultType = old('type', $deliveryAvailable ? 'delivery' : 'pickup');
    $defaultPayment = old('payment_method', $onlineAvailable ? 'razorpay' : 'cod');
    $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();
@endphp

<section class="wrap pb-24 pt-36" x-data="{ type: @js($defaultType), submitting: false }" x-init="$store.cart.setType(type)">
    <p class="eyebrow">Checkout</p>
    <h1 class="display mt-4 text-4xl text-cream md:text-5xl">Almost at your table</h1>
    <div class="ornament mt-6"><span></span></div>

    @guest
        <p class="mt-8 border border-gold/20 bg-gold/5 px-5 py-4 text-cream/90">
            <a href="{{ route('login', ['next' => 'checkout']) }}" class="font-medium text-gold hover:text-gold-light">Sign in</a> or <a href="{{ route('register', ['next' => 'checkout']) }}" class="font-medium text-gold hover:text-gold-light">create an account</a> to save your address and see all your orders. You can also continue as a guest below.
        </p>
    @endguest

    <form method="POST" action="{{ route('checkout.store') }}" class="mt-12 grid gap-10 lg:grid-cols-5" @submit="submitting = true">
        @csrf

        <div class="space-y-10 lg:col-span-3">
            {{-- Order type --}}
            <fieldset>
                <legend class="font-display text-xl tracking-widest text-gold-light">1 · How would you like it?</legend>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @if ($deliveryAvailable)
                        <label class="tile">
                            <input type="radio" name="type" value="delivery" x-model="type" @change="$store.cart.setType('delivery')">
                            <span><span class="block font-medium text-cream">Home delivery</span><span class="mt-1 block text-sm text-sand">Hot food at your door in Kairana.</span></span>
                        </label>
                    @endif
                    <label class="tile">
                        <input type="radio" name="type" value="pickup" x-model="type" @change="$store.cart.setType('pickup')">
                        <span><span class="block font-medium text-cream">Takeaway</span><span class="mt-1 block text-sm text-sand">Collect from the restaurant counter.</span></span>
                    </label>
                </div>
                @error('type')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>

            {{-- Details --}}
            <fieldset>
                <legend class="font-display text-xl tracking-widest text-gold-light">2 · Your details</legend>
                <div class="mt-5 grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="field-label" for="customer_name">Full name *</label>
                        <input class="field" id="customer_name" name="customer_name" value="{{ old('customer_name', $customer?->name) }}" required maxlength="100" autocomplete="name">
                        @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="field-label" for="phone">Mobile number *</label>
                        <input class="field" id="phone" name="phone" type="tel" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" value="{{ old('phone', $customer?->phone) }}" required autocomplete="tel-national" placeholder="10 digit mobile">
                        @error('phone')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @if ($addresses->isNotEmpty())
                        <div class="md:col-span-2" x-show="type === 'delivery'">
                            <span class="field-label">Deliver to a saved address</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($addresses as $saved)
                                    <button type="button" class="border border-gold/30 px-4 py-2 text-left text-sm text-cream/90 transition-colors hover:border-gold"
                                            @click="$refs.address.value = @js($saved->address); $refs.landmark.value = @js((string) $saved->landmark)">
                                        <span class="block text-[0.62rem] font-medium uppercase tracking-[0.2em] text-gold">{{ $saved->label }}</span>
                                        {{ Str::limit($saved->address, 38) }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div class="md:col-span-2" x-show="type === 'delivery'" x-transition>
                        <label class="field-label" for="address">Delivery address *</label>
                        <textarea class="field" id="address" name="address" x-ref="address" rows="2" maxlength="500" :required="type === 'delivery'" autocomplete="street-address" placeholder="House no., street, mohalla">{{ old('address', $defaultAddress?->address) }}</textarea>
                        @error('address')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2" x-show="type === 'delivery'" x-transition>
                        <label class="field-label" for="landmark">Landmark</label>
                        <input class="field" id="landmark" name="landmark" x-ref="landmark" value="{{ old('landmark', $defaultAddress?->landmark) }}" maxlength="150" placeholder="Near…">
                        @auth
                            <label class="mt-4 flex cursor-pointer items-center gap-3 text-cream/90">
                                <input type="checkbox" name="save_address" value="1" class="size-4 accent-[#c9a45c]" @checked(old('save_address', $addresses->isEmpty()))> Save this address to my account
                            </label>
                        @endauth
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label" for="notes">Cooking instructions</label>
                        <input class="field" id="notes" name="notes" value="{{ old('notes') }}" maxlength="500" placeholder="Less spicy, extra gravy…">
                    </div>
                </div>
            </fieldset>

            {{-- Payment --}}
            <fieldset>
                <legend class="font-display text-xl tracking-widest text-gold-light">3 · Payment</legend>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @if ($onlineAvailable)
                        <label class="tile">
                            <input type="radio" name="payment_method" value="razorpay" @checked($defaultPayment === 'razorpay')>
                            <span><span class="block font-medium text-cream">Pay online</span><span class="mt-1 block text-sm text-sand">UPI, cards, net banking &amp; wallets — secured by Razorpay.</span></span>
                        </label>
                    @endif
                    @if ($codAvailable)
                        <label class="tile">
                            <input type="radio" name="payment_method" value="cod" @checked($defaultPayment === 'cod' || ! $onlineAvailable)>
                            <span><span class="block font-medium text-cream" x-text="type === 'delivery' ? 'Cash on delivery' : 'Pay at counter'">Cash on delivery</span><span class="mt-1 block text-sm text-sand">Pay in cash when you receive your food.</span></span>
                        </label>
                    @endif
                </div>
                @if (! $onlineAvailable && ! $codAvailable)
                    <p class="field-error">No payment method is available right now. Please call us to place your order.</p>
                @endif
                @error('payment_method')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>
        </div>

        {{-- Summary --}}
        <aside class="lg:col-span-2">
            <div class="card sticky top-28 p-6 md:p-8">
                <p class="eyebrow">Order summary</p>
                <ul class="mt-5 divide-y divide-white/5">
                    <template x-for="item in $store.cart.data.items" :key="item.id">
                        <li class="flex items-center gap-4 py-4">
                            <img :src="item.image" alt="" class="size-14 flex-none object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="leading-snug text-cream" x-text="item.name"></p>
                                <div class="stepper mt-2 scale-90 origin-left">
                                    <button type="button" @click="$store.cart.set(item.id, item.quantity - 1)" aria-label="Decrease">−</button>
                                    <span x-text="item.quantity"></span>
                                    <button type="button" @click="$store.cart.set(item.id, item.quantity + 1)" aria-label="Increase">+</button>
                                </div>
                            </div>
                            <p class="text-gold-light" x-text="$store.cart.money(item.total)"></p>
                        </li>
                    </template>
                </ul>

                {{-- Coupon --}}
                <div class="mt-4 border-t border-gold/15 pt-5" x-data="{ code: '' }">
                    <template x-if="$store.cart.data.coupon_code && $store.cart.data.discount > 0">
                        <div class="flex items-center justify-between gap-3 border border-leaf/50 bg-leaf/10 px-4 py-3">
                            <p class="text-sm text-cream"><span class="font-medium tracking-widest" x-text="$store.cart.data.coupon_code"></span> applied</p>
                            <button type="button" class="text-xs uppercase tracking-[0.2em] text-sand hover:text-gold" @click="$store.cart.applyCoupon('')">Remove</button>
                        </div>
                    </template>
                    <template x-if="!($store.cart.data.coupon_code && $store.cart.data.discount > 0)">
                        <div>
                            <label class="field-label" for="coupon">Have a coupon?</label>
                            <div class="flex gap-2">
                                <input class="field !py-2.5 uppercase" id="coupon" x-model="code" maxlength="40" placeholder="Enter code" autocapitalize="characters" @keydown.enter.prevent="code && $store.cart.applyCoupon(code)">
                                <button type="button" class="btn btn-line btn-sm flex-none" :disabled="!code || $store.cart.busy" @click="$store.cart.applyCoupon(code)">Apply</button>
                            </div>
                        </div>
                    </template>
                </div>

                <dl class="mt-4 space-y-3 border-t border-gold/15 pt-5 text-cream/90">
                    <div class="flex justify-between"><dt class="text-sand">Subtotal</dt><dd x-text="$store.cart.money($store.cart.data.subtotal)">{{ money($summary['subtotal']) }}</dd></div>
                    <div class="flex justify-between text-leaf" x-cloak x-show="$store.cart.data.discount > 0"><dt>Coupon discount</dt><dd x-text="'− ' + $store.cart.money($store.cart.data.discount)"></dd></div>
                    <div class="flex justify-between" x-show="type === 'delivery'"><dt class="text-sand">Delivery</dt><dd x-text="$store.cart.data.delivery_charge > 0 ? $store.cart.money($store.cart.data.delivery_charge) : 'Free'"></dd></div>
                    <div class="flex justify-between" x-show="$store.cart.data.tax > 0"><dt class="text-sand">Taxes</dt><dd x-text="$store.cart.money($store.cart.data.tax)"></dd></div>
                    <div class="flex items-baseline justify-between border-t border-gold/15 pt-4"><dt class="text-sm uppercase tracking-[0.22em] text-sand">Total</dt><dd class="font-display text-3xl text-gold-light" x-text="$store.cart.money($store.cart.data.total)">{{ money($summary['total']) }}</dd></div>
                </dl>

                <p x-cloak x-show="type === 'delivery' && $store.cart.data.free_delivery_above > 0 && $store.cart.data.delivery_charge > 0" class="mt-4 text-sm text-sand">
                    Add <span class="text-gold-light" x-text="$store.cart.money($store.cart.data.free_delivery_above - $store.cart.data.subtotal)"></span> more for free delivery.
                </p>
                <p x-cloak x-show="!$store.cart.data.meets_minimum" class="field-error">Minimum order is <span x-text="$store.cart.money($store.cart.data.min_order)"></span>.</p>

                <button class="btn btn-gold mt-7 w-full" :disabled="submitting || !$store.cart.data.meets_minimum || $store.cart.data.count === 0 || {{ (! $onlineAvailable && ! $codAvailable) ? 'true' : 'false' }}">
                    <span x-show="!submitting">Place order</span><span x-cloak x-show="submitting">Placing your order…</span>
                </button>
                <a href="{{ route('menu') }}" class="mt-4 block text-center text-sm text-sand hover:text-gold">← Add more dishes</a>
            </div>
        </aside>
    </form>
</section>
@endsection
