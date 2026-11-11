{{-- Expects $item (MenuItem). Optional $delay for staggered reveal. --}}
<article class="card group reveal flex w-full flex-col" style="--d: {{ $delay ?? 0 }}ms">
    <div class="zoom relative aspect-[4/3]">
        <a href="{{ route('dish', $item) }}" class="block size-full" aria-label="{{ $item->name }} — details">
            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" class="size-full object-cover">
            <span class="absolute inset-0 bg-gradient-to-t from-ink-2 via-transparent to-transparent"></span>
        </a>
        <div class="pointer-events-none absolute left-3 top-3 flex flex-wrap gap-2">
            @if ($item->is_bestseller)<span class="chip">Bestseller</span>@endif
            @if ($item->has_discount)<span class="chip bg-ruby text-cream">Offer</span>@endif
            @if ($item->stock_qty !== null && $item->stock_qty > 0 && $item->stock_qty <= 5)<span class="chip bg-ink text-gold-light">Only {{ $item->stock_qty }} left</span>@endif
        </div>
        <button type="button" @click="$store.favs.toggle({{ $item->id }})"
                class="absolute right-3 top-3 grid size-10 place-items-center rounded-full bg-ink/70 text-cream backdrop-blur transition-colors hover:text-gold"
                :class="$store.favs.has({{ $item->id }}) && '!text-ruby'"
                :aria-pressed="$store.favs.has({{ $item->id }})" aria-label="Save {{ $item->name }} to favourites">
            <svg class="size-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" :fill="$store.favs.has({{ $item->id }}) ? 'currentColor' : 'none'" fill="none"><path d="M12 20s-7-4.400-7-10a4 4 0 0 1 7-2.600A4 4 0 0 1 19 10c0 5.600-7 10-7 10Z"/></svg>
        </button>
    </div>

    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-start gap-2.5">
            <span class="diet mt-1.5 {{ $item->is_veg ? 'veg' : '' }}" title="{{ $item->is_veg ? 'Vegetarian' : 'Non-vegetarian' }}"></span>
            <h3 class="font-display text-lg leading-snug tracking-wider text-cream"><a href="{{ route('dish', $item) }}" class="transition-colors hover:text-gold">{{ $item->name }}</a></h3>
        </div>
        @if ($item->portion)<p class="mt-1 pl-6 text-xs uppercase tracking-[0.2em] text-gold/80">{{ $item->portion }}</p>@endif
        <p class="mt-3 flex-1 text-[0.95rem] leading-relaxed text-sand">{{ $item->description }}</p>

        <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/5 pt-4">
            <p class="flex items-baseline gap-2">
                <span class="font-display text-xl text-gold-light">{{ money($item->final_price) }}</span>
                @if ($item->has_discount)<s class="text-sm text-sand/70">{{ money($item->price) }}</s>@endif
            </p>
            @include('site.partials.add-control', ['item' => $item])
        </div>
    </div>
</article>
