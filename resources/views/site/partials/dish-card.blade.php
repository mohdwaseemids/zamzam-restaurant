{{-- Expects $item (MenuItem). Optional $delay for staggered reveal. --}}
<article class="card group reveal flex flex-col" style="--d: {{ $delay ?? 0 }}ms">
    <div class="zoom relative aspect-[4/3]">
        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" class="size-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-ink-2 via-transparent to-transparent"></div>
        <div class="absolute left-3 top-3 flex flex-wrap gap-2">
            @if ($item->is_bestseller)<span class="chip">Bestseller</span>@endif
            @if ($item->has_discount)<span class="chip bg-ruby text-cream">Offer</span>@endif
        </div>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-start gap-2.5">
            <span class="diet mt-1.5 {{ $item->is_veg ? 'veg' : '' }}" title="{{ $item->is_veg ? 'Vegetarian' : 'Non-vegetarian' }}"></span>
            <h3 class="font-display text-lg leading-snug tracking-wider text-cream">{{ $item->name }}</h3>
        </div>
        @if ($item->portion)<p class="mt-1 pl-6 text-xs uppercase tracking-[0.2em] text-gold/80">{{ $item->portion }}</p>@endif
        <p class="mt-3 flex-1 text-[0.95rem] leading-relaxed text-sand">{{ $item->description }}</p>

        <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/5 pt-4">
            <p class="flex items-baseline gap-2">
                <span class="font-display text-xl text-gold-light">{{ money($item->final_price) }}</span>
                @if ($item->has_discount)<s class="text-sm text-sand/70">{{ money($item->price) }}</s>@endif
            </p>

            @if (setting('ordering_enabled', true))
                <div>
                    <button type="button" x-show="$store.cart.qty({{ $item->id }}) === 0" @click="$store.cart.add({{ $item->id }}, @js($item->name))" class="btn btn-line btn-sm">Add <span aria-hidden="true">+</span></button>
                    <div x-cloak x-show="$store.cart.qty({{ $item->id }}) > 0" class="stepper">
                        <button type="button" @click="$store.cart.set({{ $item->id }}, $store.cart.qty({{ $item->id }}) - 1)" aria-label="Decrease {{ $item->name }}">−</button>
                        <span x-text="$store.cart.qty({{ $item->id }})"></span>
                        <button type="button" @click="$store.cart.set({{ $item->id }}, $store.cart.qty({{ $item->id }}) + 1)" aria-label="Increase {{ $item->name }}">+</button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</article>
