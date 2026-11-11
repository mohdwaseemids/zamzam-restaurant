@extends('layouts.site')

@section('title', $item->name.' — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('description', Str::limit((string) $item->description, 150))
@section('header-class', 'bg-ink')

@section('content')
@php
    $viewer = $photos->map(fn ($src) => ['src' => $src, 'title' => $item->name])->values();
    $spice = [0 => 'No spice', 1 => 'Mild', 2 => 'Medium', 3 => 'Spicy'][$item->spice_level] ?? null;
@endphp

<section class="wrap pb-20 pt-32 lg:pt-36">
    <nav class="text-xs uppercase tracking-[0.22em] text-sand" aria-label="Breadcrumb">
        <a href="{{ route('menu') }}" class="hover:text-gold">Menu</a>
        <span class="mx-2 text-white/20">/</span>
        <a href="{{ route('menu') }}#{{ $item->category->slug }}" class="hover:text-gold">{{ $item->category->name }}</a>
    </nav>

    <div class="mt-8 grid gap-10 lg:grid-cols-2 lg:gap-16">
        {{-- Photos --}}
        <div x-data="{ active: 0, photos: @js($viewer) }">
            <button type="button" class="zoom relative block aspect-[4/3] w-full border border-gold/20 bg-ink-3" @click="$store.viewer.photos(photos, active)" aria-label="Open photo">
                <img :src="photos[active]?.src ?? @js($item->image_url)" src="{{ $item->image_url }}" alt="{{ $item->name }}" class="size-full object-cover">
                <span class="pointer-events-none absolute left-4 top-4 flex flex-wrap gap-2">
                    @if ($item->is_bestseller)<span class="chip">Bestseller</span>@endif
                    @if ($item->has_discount)<span class="chip bg-ruby text-cream">Offer</span>@endif
                </span>
            </button>

            @if ($photos->count() > 1)
                <div class="scrollbar-none mt-3 flex gap-3 overflow-x-auto">
                    @foreach ($photos as $index => $photo)
                        <button type="button" @click="active = {{ $index }}" class="size-20 flex-none border transition-colors" :class="active === {{ $index }} ? 'border-gold' : 'border-white/10 opacity-70 hover:opacity-100'" aria-label="Photo {{ $index + 1 }}">
                            <img src="{{ $photo }}" alt="" loading="lazy" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <p class="eyebrow">{{ $item->category->name }}</p>
            <h1 class="display mt-4 flex items-start gap-3 text-3xl text-cream md:text-5xl">
                <span class="diet mt-3 {{ $item->is_veg ? 'veg' : '' }}" title="{{ $item->is_veg ? 'Vegetarian' : 'Non-vegetarian' }}"></span>
                <span>{{ $item->name }}</span>
            </h1>

            @if ($rating['count'])
                <a href="#reviews" class="mt-4 inline-flex items-center gap-2 text-sand hover:text-gold">
                    <span class="tracking-[0.15em] text-gold">{{ str_repeat('★', (int) round($rating['average'])) }}</span>
                    {{ $rating['average'] }} · {{ $rating['count'] }} {{ Str::plural('review', $rating['count']) }}
                </a>
            @endif

            <div class="ornament mt-6"><span></span></div>

            @if ($item->description)<p class="lead mt-6">{{ $item->description }}</p>@endif

            <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                @if ($item->portion)<div><dt class="text-[0.62rem] uppercase tracking-[0.24em] text-sand">Portion</dt><dd class="mt-1 text-cream">{{ $item->portion }}</dd></div>@endif
                @if ($spice)<div><dt class="text-[0.62rem] uppercase tracking-[0.24em] text-sand">Spice</dt><dd class="mt-1 text-cream">{{ $spice }}</dd></div>@endif
                <div><dt class="text-[0.62rem] uppercase tracking-[0.24em] text-sand">Type</dt><dd class="mt-1 text-cream">{{ $item->is_veg ? 'Vegetarian' : 'Non-vegetarian' }}</dd></div>
                @if ($item->stock_qty !== null && $item->stock_qty > 0 && $item->stock_qty <= 5)
                    <div><dt class="text-[0.62rem] uppercase tracking-[0.24em] text-sand">Availability</dt><dd class="mt-1 text-[#e5c07f]">Only {{ $item->stock_qty }} left today</dd></div>
                @endif
            </dl>

            <div class="mt-10 flex flex-wrap items-center gap-6 border-t border-gold/15 pt-8">
                <p class="flex items-baseline gap-3">
                    <span class="font-display text-4xl text-gold-light">{{ money($item->final_price) }}</span>
                    @if ($item->has_discount)<s class="text-lg text-sand/70">{{ money($item->price) }}</s>@endif
                </p>
                @include('site.partials.add-control', ['item' => $item])
                <button type="button" @click="$store.favs.toggle({{ $item->id }})" class="inline-flex items-center gap-2 text-xs font-medium uppercase tracking-[0.22em] text-sand transition-colors hover:text-gold" :class="$store.favs.has({{ $item->id }}) && '!text-ruby'">
                    <svg class="size-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" :fill="$store.favs.has({{ $item->id }}) ? 'currentColor' : 'none'" fill="none"><path d="M12 20s-7-4.400-7-10a4 4 0 0 1 7-2.600A4 4 0 0 1 19 10c0 5.600-7 10-7 10Z"/></svg>
                    <span x-text="$store.favs.has({{ $item->id }}) ? 'Saved' : 'Save'">Save</span>
                </button>
            </div>

            <div x-data x-cloak x-show="$store.cart.data.count > 0" class="mt-6">
                <button type="button" @click="$store.cart.open = true" class="link-gold">View your order (<span x-text="$store.cart.data.count"></span>)</button>
            </div>
        </div>
    </div>
</section>

{{-- Reviews --}}
<section id="reviews" class="section lattice border-y border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Guest reviews', 'title' => $rating['count'] ? 'What guests say about it' : 'Tried it? Tell us'])

        @if ($reviews->isNotEmpty())
            <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    @include('site.partials.review-card', ['review' => $review, 'delay' => ($loop->index % 3) * 80])
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('reviews.store') }}" enctype="multipart/form-data" class="card reveal mx-auto mt-12 grid max-w-3xl gap-6 p-6 md:grid-cols-2 md:p-10" x-data="{ rating: {{ (int) old('rating', 5) }}, hover: 0 }">
            @csrf
            <input type="hidden" name="menu_item_id" value="{{ $item->id }}">
            <div class="md:col-span-2">
                <span class="field-label">Rate {{ $item->name }}</span>
                <div class="flex gap-1" @mouseleave="hover = 0">
                    @for ($star = 1; $star <= 5; $star++)
                        <button type="button" @click="rating = {{ $star }}" @mouseenter="hover = {{ $star }}" class="text-4xl leading-none transition-colors" :class="(hover || rating) >= {{ $star }} ? 'text-gold' : 'text-white/15'" aria-label="{{ $star }} {{ Str::plural('star', $star) }}">★</button>
                    @endfor
                </div>
                <input type="hidden" name="rating" :value="rating">
            </div>
            <div>
                <label class="field-label" for="r-name">Your name *</label>
                <input class="field" id="r-name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required maxlength="100">
                @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="r-city">City</label>
                <input class="field" id="r-city" name="city" value="{{ old('city') }}" maxlength="100" placeholder="Kairana">
            </div>
            <div class="md:col-span-2">
                <label class="field-label" for="r-comment">Your review *</label>
                <textarea class="field" id="r-comment" name="comment" rows="3" required minlength="10" maxlength="1000">{{ old('comment') }}</textarea>
                @error('comment')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <button class="btn btn-gold w-full md:w-auto">Submit review</button>
                <p class="mt-3 text-sm text-sand">Reviews appear after a quick check by our team.</p>
            </div>
        </form>
    </div>
</section>

@if ($related->isNotEmpty())
<section class="section">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Goes well with', 'title' => 'More from '.e($item->category->name)])
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($related as $other)
                @include('site.partials.dish-card', ['item' => $other, 'delay' => $loop->index * 80])
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
