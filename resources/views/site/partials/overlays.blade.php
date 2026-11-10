{{-- ================= Cart drawer ================= --}}
<div x-cloak x-show="$store.cart.open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Your order">
    <div x-show="$store.cart.open" x-transition.opacity.duration.400ms class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$store.cart.open = false"></div>

    <aside x-show="$store.cart.open"
           x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-300 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-gold/25 bg-ink-2 shadow-2xl">
        <header class="flex items-center justify-between border-b border-gold/15 px-6 py-5">
            <div>
                <p class="eyebrow">Your order</p>
                <p class="mt-1 font-display text-xl tracking-widest text-cream"><span x-text="$store.cart.data.count"></span> <span x-text="$store.cart.data.count === 1 ? 'item' : 'items'"></span></p>
            </div>
            <button type="button" @click="$store.cart.open = false" class="grid size-10 place-items-center border border-gold/30 text-gold-light hover:bg-gold hover:text-ink" aria-label="Close">✕</button>
        </header>

        <div class="flex-1 overflow-y-auto px-6">
            <template x-if="$store.cart.data.items.length === 0">
                <div class="grid h-full place-items-center py-16 text-center">
                    <div>
                        <div class="ornament justify-center"><span></span></div>
                        <p class="mt-6 font-serif text-2xl italic text-cream">Your table is still empty</p>
                        <p class="mt-2 text-sand">Add a few dishes and we will start cooking.</p>
                        <a href="{{ route('menu') }}" class="btn btn-gold mt-8">Browse the menu</a>
                    </div>
                </div>
            </template>

            <ul class="divide-y divide-white/5">
                <template x-for="item in $store.cart.data.items" :key="item.id">
                    <li class="flex gap-4 py-5">
                        <img :src="item.image" :alt="item.name" class="size-20 flex-none object-cover" loading="lazy">
                        <div class="min-w-0 flex-1">
                            <p class="flex items-start gap-2 font-medium text-cream"><span class="diet mt-1" :class="item.is_veg && 'veg'"></span><span class="leading-snug" x-text="item.name"></span></p>
                            <p class="mt-0.5 text-sm text-sand" x-text="item.portion"></p>
                            <div class="mt-3 flex items-center justify-between">
                                <div class="stepper">
                                    <button type="button" @click="$store.cart.set(item.id, item.quantity - 1)" aria-label="Decrease">−</button>
                                    <span x-text="item.quantity"></span>
                                    <button type="button" @click="$store.cart.set(item.id, item.quantity + 1)" aria-label="Increase">+</button>
                                </div>
                                <p class="font-medium text-gold-light" x-text="$store.cart.money(item.total)"></p>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <footer x-show="$store.cart.data.items.length > 0" class="border-t border-gold/15 bg-ink px-6 py-5">
            <div class="flex items-center justify-between text-cream">
                <span class="text-sm uppercase tracking-[0.22em] text-sand">Subtotal</span>
                <span class="font-display text-2xl text-gold-light" x-text="$store.cart.money($store.cart.data.subtotal)"></span>
            </div>
            <p x-show="!$store.cart.data.meets_minimum" class="mt-2 text-sm text-[#e58b7f]">Minimum order is <span x-text="$store.cart.money($store.cart.data.min_order)"></span>.</p>
            <p x-show="$store.cart.data.meets_minimum" class="mt-2 text-sm text-sand">Delivery charges are shown at checkout.</p>
            <a href="{{ route('checkout') }}" class="btn btn-gold mt-5 w-full" :class="!$store.cart.data.meets_minimum && 'pointer-events-none opacity-50'">Proceed to checkout</a>
        </footer>
    </aside>
</div>

{{-- ================= Photo / video viewer ================= --}}
<div x-cloak x-show="$store.viewer.open" x-transition.opacity.duration.300ms class="fixed inset-0 z-[60] flex items-center justify-center bg-black/95 p-4"
     role="dialog" aria-modal="true" aria-label="Media viewer"
     @keydown.arrow-right.window="$store.viewer.step(1)" @keydown.arrow-left.window="$store.viewer.step(-1)" @click.self="$store.viewer.close()">
    <button type="button" @click="$store.viewer.close()" class="absolute right-4 top-4 z-10 grid size-11 place-items-center border border-gold/40 text-gold-light hover:bg-gold hover:text-ink" aria-label="Close">✕</button>

    <template x-if="$store.viewer.open && $store.viewer.kind === 'image'">
        <figure class="flex max-h-full max-w-6xl flex-col items-center">
            <img :src="$store.viewer.current.src" :alt="$store.viewer.current.title" class="max-h-[82vh] w-auto max-w-full object-contain">
            <figcaption class="mt-4 text-center font-serif text-xl italic text-sand" x-text="$store.viewer.current.title"></figcaption>
        </figure>
    </template>

    <template x-if="$store.viewer.open && $store.viewer.kind === 'embed'">
        <div class="w-full" :class="$store.viewer.current.vertical ? 'max-w-sm' : 'max-w-5xl'">
            <div class="relative w-full overflow-hidden border border-gold/30 bg-black" :class="$store.viewer.current.vertical ? 'aspect-[9/16]' : 'aspect-video'">
                <iframe :src="$store.viewer.current.embed" :title="$store.viewer.current.title" class="absolute inset-0 size-full" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
            </div>
            <p class="mt-4 text-center font-serif text-xl italic text-sand" x-text="$store.viewer.current.title"></p>
        </div>
    </template>

    <template x-if="$store.viewer.open && $store.viewer.kind === 'file'">
        <div class="w-full max-w-5xl">
            <video :src="$store.viewer.current.file" controls autoplay playsinline class="max-h-[80vh] w-full border border-gold/30 bg-black"></video>
            <p class="mt-4 text-center font-serif text-xl italic text-sand" x-text="$store.viewer.current.title"></p>
        </div>
    </template>

    <template x-if="$store.viewer.kind === 'image' && $store.viewer.items.length > 1">
        <div>
            <button type="button" @click="$store.viewer.step(-1)" class="absolute left-3 top-1/2 grid size-12 -translate-y-1/2 place-items-center border border-gold/40 bg-black/50 text-xl text-gold-light hover:bg-gold hover:text-ink" aria-label="Previous">‹</button>
            <button type="button" @click="$store.viewer.step(1)" class="absolute right-3 top-1/2 grid size-12 -translate-y-1/2 place-items-center border border-gold/40 bg-black/50 text-xl text-gold-light hover:bg-gold hover:text-ink" aria-label="Next">›</button>
        </div>
    </template>
</div>

{{-- ================= Toast ================= --}}
<div x-cloak x-show="$store.toast.visible" x-transition.opacity
     class="pointer-events-none fixed inset-x-4 bottom-20 z-[70] mx-auto w-fit max-w-sm border px-5 py-3 text-sm shadow-2xl lg:bottom-8"
     :class="$store.toast.error ? 'border-ruby bg-ink-3 text-cream' : 'border-gold bg-gold text-ink'" role="status" aria-live="polite">
    <span x-text="$store.toast.message"></span>
</div>
