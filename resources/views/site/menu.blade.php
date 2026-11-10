@extends('layouts.site')

@section('title', 'Menu — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('content')
@include('site.partials.page-hero', [
    'eyebrow' => 'The menu',
    'title' => 'Cooked slow, <span class="gold-text">served warm</span>',
    'lead' => setting('ordering_enabled', true) ? 'Order online for home delivery or takeaway — or simply come hungry.' : 'Everything is made fresh to order.',
    'image' => media_url(setting('hero_image')),
])

<div x-data="menuPage">
    {{-- Sticky filter bar --}}
    <div class="sticky top-[4.3rem] z-30 border-y border-gold/15 bg-ink/95 backdrop-blur-md">
        <div class="wrap flex flex-col gap-3 py-3 lg:flex-row lg:items-center lg:gap-6">
            <div class="scrollbar-none -mx-5 flex flex-1 gap-2 overflow-x-auto px-5 lg:mx-0 lg:px-0">
                @foreach ($categories as $category)
                    <a href="#{{ $category->slug }}"
                       class="flex-none border px-4 py-2 text-[0.68rem] font-medium uppercase tracking-[0.2em] transition-colors"
                       :class="active === '{{ $category->slug }}' ? 'border-gold bg-gold text-ink' : 'border-gold/25 text-cream/80 hover:border-gold hover:text-gold'">{{ $category->name }}</a>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <label class="relative flex-1 lg:w-64">
                    <span class="sr-only">Search dishes</span>
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.500-4.500"/></svg>
                    <input type="search" x-model="q" placeholder="Search dishes…" class="field !py-2.5 !pl-10 text-sm">
                </label>
                <label class="flex flex-none cursor-pointer items-center gap-2 border border-gold/25 px-3 py-2.5 text-[0.68rem] font-medium uppercase tracking-[0.18em] text-cream/80" :class="veg && '!border-leaf !text-leaf'">
                    <input type="checkbox" x-model="veg" class="sr-only"><span class="diet veg"></span> Veg only
                </label>
            </div>
        </div>
    </div>

    <div class="wrap pb-24">
        @foreach ($categories as $category)
            @php $meta = $category->menuItems->map(fn ($i) => [mb_strtolower($i->name), $i->is_veg])->values(); @endphp
            <section id="{{ $category->slug }}" data-category="{{ $category->slug }}" class="scroll-mt-44 pt-20 lg:scroll-mt-32" x-show="any(@js($meta))">
                <div class="flex flex-col items-start justify-between gap-4 border-b border-gold/15 pb-6 md:flex-row md:items-end">
                    <div>
                        <p class="eyebrow">{{ $category->tagline }}</p>
                        <h2 class="display mt-3 text-3xl text-cream md:text-4xl">{{ $category->name }}</h2>
                    </div>
                    @if ($category->description)<p class="max-w-md font-serif text-lg italic text-sand md:text-right">{{ $category->description }}</p>@endif
                </div>

                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($category->menuItems as $item)
                        <div x-show="match(@js(mb_strtolower($item->name)), {{ $item->is_veg ? 'true' : 'false' }})" class="flex">
                            @include('site.partials.dish-card', ['item' => $item, 'delay' => 0])
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($categories->isEmpty())
            <p class="py-24 text-center font-serif text-2xl italic text-sand">Our menu is being prepared. Please check back soon.</p>
        @else
            <p x-cloak x-show="!@js($categories->flatMap(fn ($c) => $c->menuItems->map(fn ($i) => [mb_strtolower($i->name), $i->is_veg]))->values()).some(([n, v]) => match(n, v))"
               class="py-24 text-center font-serif text-2xl italic text-sand">No dish matches your search.</p>
        @endif
    </div>
</div>

{{-- Floating checkout bar --}}
@if (setting('ordering_enabled', true))
    <div x-data x-cloak x-show="$store.cart.data.count > 0 && !$store.cart.open" x-transition
         class="fixed inset-x-4 bottom-20 z-30 mx-auto flex max-w-xl items-center justify-between gap-4 border border-gold bg-ink-3/95 px-5 py-3 shadow-2xl backdrop-blur lg:bottom-6">
        <p class="text-cream"><span class="font-medium" x-text="$store.cart.data.count"></span> <span x-text="$store.cart.data.count === 1 ? 'item' : 'items'"></span> · <span class="text-gold-light" x-text="$store.cart.money($store.cart.data.subtotal)"></span></p>
        <button type="button" @click="$store.cart.open = true" class="btn btn-gold btn-sm">View order</button>
    </div>
@endif
@endsection
