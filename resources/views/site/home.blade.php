@extends('layouts.site')

@section('content')
@php
    $heroVideo = setting('hero_video');
    $ordering = (bool) setting('ordering_enabled', true);
    $galleryPayload = $gallery->map(fn ($g) => ['src' => $g->image_url, 'title' => $g->title])->values();
@endphp

{{-- ================= Hero ================= --}}
<section class="relative isolate flex min-h-svh items-center overflow-hidden">
    @if ($heroVideo)
        <video class="absolute inset-0 -z-20 size-full object-cover" autoplay muted loop playsinline poster="{{ media_url(setting('hero_image')) }}">
            <source src="{{ media_url($heroVideo) }}">
        </video>
    @else
        <img src="{{ media_url(setting('hero_image'), 'Zam Zam') }}" alt="" class="kenburns absolute inset-0 -z-20 size-full object-cover" fetchpriority="high">
    @endif
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,rgba(11,9,7,0.55)_0%,rgba(11,9,7,0.88)_70%,#0b0907_100%)]"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-48 bg-gradient-to-t from-ink to-transparent"></div>

    <div class="wrap pb-48 pt-32 text-center md:pb-36 md:pt-36">
        <p class="rise font-arabic text-3xl text-gold/90" lang="ar" dir="rtl">أهلاً وسهلاً</p>
        <p class="eyebrow rise mt-5" style="--d: 120ms">{{ setting('hero_eyebrow', 'Kairana’s finest family dining') }}</p>
        <h1 class="display rise mx-auto mt-7 max-w-5xl text-[2.6rem] text-cream sm:text-6xl lg:text-[5.2rem]" style="--d: 240ms">
            <span class="gold-text">{{ setting('hero_title', 'Taste that brings families together') }}</span>
        </h1>
        <div class="ornament rise mt-8 justify-center" style="--d: 360ms"><span></span></div>
        <p class="lead rise mx-auto mt-7 max-w-2xl !text-cream/85" style="--d: 440ms">{{ setting('hero_subtitle') }}</p>

        <div class="rise mt-11 flex flex-col items-center justify-center gap-4 sm:flex-row" style="--d: 560ms">
            <a href="{{ route('menu') }}" class="btn btn-gold w-full sm:w-auto">{{ $ordering ? 'Order online' : 'View the menu' }}</a>
            <a href="{{ route('reserve') }}" class="btn btn-line w-full sm:w-auto">Reserve a table</a>
        </div>
    </div>

    <div class="absolute inset-x-0 bottom-0 border-t border-gold/15 bg-ink/55 backdrop-blur-md">
        <div class="wrap grid grid-cols-2 gap-y-3 py-4 text-center md:grid-cols-3 md:divide-x md:divide-gold/15">
            <div class="col-span-2 px-2 md:col-span-1">
                <p class="text-[0.6rem] uppercase tracking-[0.3em] text-sand">Open</p>
                <p class="mt-1 text-balance font-serif text-base italic leading-snug text-cream lg:text-lg">{{ setting('opening_hours', 'Every day') }}</p>
            </div>
            <div class="px-2">
                <p class="text-[0.6rem] uppercase tracking-[0.3em] text-sand">{{ $rating['count'] ? 'Guests say' : 'Kitchen' }}</p>
                <p class="mt-1 text-balance font-serif text-base italic leading-snug text-cream lg:text-lg">
                    @if ($rating['count'])<span class="text-gold">★</span> {{ $rating['average'] }} · {{ $rating['count'] }} {{ Str::plural('review', $rating['count']) }}@else 100% Halal @endif
                </p>
            </div>
            <div class="px-2">
                <p class="text-[0.6rem] uppercase tracking-[0.3em] text-sand">Find us</p>
                <p class="mt-1 text-balance font-serif text-base italic leading-snug text-cream lg:text-lg">{{ Str::before(setting('address', 'Kairana'), ',') }}</p>
            </div>
        </div>
    </div>
</section>

{{-- ================= Marquee ================= --}}
<div class="marquee border-y border-gold/15 bg-ink-2 py-5" aria-hidden="true">
    @for ($i = 0; $i < 2; $i++)
        <div>
            @foreach ($categories as $category)
                <span class="font-display text-lg tracking-[0.3em] text-gold-light/80">{{ strtoupper($category->name) }}</span>
                <span class="size-1.5 rotate-45 bg-gold"></span>
            @endforeach
        </div>
    @endfor
</div>

{{-- ================= Our story ================= --}}
<section class="section">
    <div class="wrap grid items-center gap-16 lg:grid-cols-2 lg:gap-24">
        <div class="reveal mx-auto w-full max-w-md px-4">
            <div class="arch-outline">
                <div class="arch zoom aspect-[3/4]">
                    <img src="{{ media_url(setting('about_image'), 'Our kitchen') }}" alt="Inside the Zam Zam kitchen" loading="lazy">
                </div>
            </div>
        </div>

        <div>
            @include('site.partials.heading', ['eyebrow' => 'Our story', 'title' => e(setting('about_title', 'A table where every guest is family')), 'align' => 'left'])
            <div class="reveal mt-7 space-y-5 text-lg leading-relaxed text-sand" style="--d: 120ms">
                @foreach (preg_split('/\R{2,}/', (string) setting('about_text')) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            <dl class="reveal mt-10 grid grid-cols-3 gap-4 border-t border-gold/15 pt-8" style="--d: 220ms">
                @foreach ([['Halal', '100% certified meat'], ['Charcoal', 'Live tandoor & grill'], ['Family', 'Separate family seating']] as [$term, $detail])
                    <div>
                        <dt class="font-display text-xl tracking-widest text-gold-light md:text-2xl">{{ $term }}</dt>
                        <dd class="mt-2 text-sm text-sand">{{ $detail }}</dd>
                    </div>
                @endforeach
            </dl>

            <a href="{{ route('gallery') }}" class="link-gold reveal mt-10" style="--d: 300ms">See the restaurant</a>
        </div>
    </div>
</section>

{{-- ================= Categories ================= --}}
@if ($categories->isNotEmpty())
<section class="section lattice border-y border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'The menu', 'title' => 'From our kitchen', 'lead' => 'Mutton, chicken and fish — each with its own ustad, its own fire.'])
    </div>

    <div class="scrollbar-none mt-14 flex snap-x gap-6 overflow-x-auto px-5 pb-4 md:px-10 xl:justify-center">
        @foreach ($categories as $category)
            <a href="{{ route('menu') }}#{{ $category->slug }}" class="group reveal w-56 flex-none snap-start text-center md:w-60" style="--d: {{ $loop->index * 70 }}ms">
                <div class="arch zoom aspect-[3/4] border border-gold/20">
                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-transparent"></div>
                    <span class="absolute inset-x-0 bottom-4 text-[0.62rem] uppercase tracking-[0.28em] text-gold-light">{{ $category->menu_items_count }} dishes</span>
                </div>
                <h3 class="mt-5 font-display text-lg tracking-[0.16em] text-cream transition-colors group-hover:text-gold">{{ $category->name }}</h3>
                <p class="mt-1 font-serif italic text-sand">{{ $category->tagline }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- ================= Signature dishes ================= --}}
@if ($signature->isNotEmpty())
<section class="section">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Chef’s signatures', 'title' => 'The dishes people travel for'])

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($signature as $item)
                @include('site.partials.dish-card', ['item' => $item, 'delay' => ($loop->index % 4) * 90])
            @endforeach
        </div>

        <div class="reveal mt-14 text-center">
            <a href="{{ route('menu') }}" class="btn btn-line">View the full menu</a>
        </div>
    </div>
</section>
@endif

{{-- ================= Videos ================= --}}
@if ($videos->isNotEmpty())
<section class="section lattice border-y border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Live from our tables', 'title' => 'Hear it from our guests', 'lead' => 'Real families, real reactions — filmed right here at the restaurant.'])

        <div class="mt-14 grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
            @foreach ($videos as $video)
                @include('site.partials.video-card', ['video' => $video, 'delay' => ($loop->index % 4) * 90])
            @endforeach
        </div>

        <div class="reveal mt-12 text-center"><a href="{{ route('stories') }}" class="link-gold">Watch all videos</a></div>
    </div>
</section>
@endif

{{-- ================= Gallery ================= --}}
@if ($gallery->isNotEmpty())
<section class="section">
    <div class="wrap">
        <div class="flex flex-col items-end justify-between gap-8 md:flex-row">
            @include('site.partials.heading', ['eyebrow' => 'Gallery', 'title' => 'A feast for the eyes', 'align' => 'left'])
            <a href="{{ route('gallery') }}" class="link-gold reveal flex-none">Open gallery</a>
        </div>

        <div class="mt-14 grid auto-rows-[11rem] grid-cols-2 gap-3 md:auto-rows-[15rem] md:grid-cols-4 md:gap-4">
            @foreach ($gallery as $image)
                @php $span = match ($loop->index % 8) { 0 => 'col-span-2 row-span-2', 3 => 'row-span-2', 6 => 'md:col-span-2', default => '' }; @endphp
                <button type="button" class="group zoom reveal relative {{ $span }}" style="--d: {{ ($loop->index % 4) * 70 }}ms"
                        @click="$store.viewer.photos(@js($galleryPayload), {{ $loop->index }})" aria-label="Open photo: {{ $image->title }}">
                    <img src="{{ $image->image_url }}" alt="{{ $image->title }}" loading="lazy" class="size-full object-cover">
                    <span class="absolute inset-0 flex items-end bg-gradient-to-t from-ink/90 via-transparent to-transparent p-4 opacity-0 transition-opacity duration-500 group-hover:opacity-100">
                        <span class="font-serif text-lg italic text-cream">{{ $image->title }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ================= Reviews ================= --}}
@if ($reviews->isNotEmpty())
<section class="section lattice border-y border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Guest book', 'title' => 'Words from our guests'])

        <div class="mt-14 grid gap-6 md:grid-cols-3">
            @foreach ($reviews->take(6) as $review)
                @include('site.partials.review-card', ['review' => $review, 'delay' => ($loop->index % 3) * 90])
            @endforeach
        </div>

        <div class="reveal mt-12 text-center"><a href="{{ route('stories') }}#share" class="link-gold">Share your experience</a></div>
    </div>
</section>
@endif

{{-- ================= Reservation band ================= --}}
<section class="relative isolate overflow-hidden py-28 lg:py-40">
    <img src="{{ $gallery->firstWhere('category', 'ambience')?->image_url ?? media_url(setting('hero_image')) }}" alt="" loading="lazy" class="absolute inset-0 -z-20 size-full object-cover">
    <div class="absolute inset-0 -z-10 bg-ink/80"></div>
    <div class="wrap reveal text-center">
        <p class="eyebrow">Celebrations · Family dinners · Parties</p>
        <h2 class="display mx-auto mt-6 max-w-3xl text-4xl text-cream md:text-6xl">Your table is <span class="gold-text">waiting</span></h2>
        <p class="lead mx-auto mt-6 max-w-xl !text-cream/80">Birthdays, dawats or a quiet family dinner — tell us the day and we will keep the best seats for you.</p>
        <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
            <a href="{{ route('reserve') }}" class="btn btn-gold w-full sm:w-auto">Book a table</a>
            @if (setting('phone'))<a href="tel:{{ setting('phone') }}" class="btn btn-line w-full sm:w-auto">Call {{ setting('phone') }}</a>@endif
        </div>
    </div>
</section>

{{-- ================= Instagram ================= --}}
<section class="section">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Instagram', 'title' => '@'.e(setting('instagram_handle', 'zamzamkairana')), 'lead' => 'Fresh from the tandoor to your feed. Follow us for daily specials and offers.'])

        @if ($instagram->isNotEmpty())
            <div class="mt-14 grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                @foreach ($instagram as $post)
                    <a href="{{ $post->permalink ?: setting('instagram_url') }}" target="_blank" rel="noopener" class="group zoom reveal relative aspect-square" style="--d: {{ ($loop->index % 4) * 70 }}ms" aria-label="Open Instagram post">
                        <img src="{{ $post->image_url }}" alt="{{ Str::limit($post->caption, 80) }}" loading="lazy" class="size-full object-cover">
                        <span class="absolute inset-0 grid place-items-center bg-ink/70 opacity-0 transition-opacity duration-500 group-hover:opacity-100">
                            <svg class="size-8 text-gold-light" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/></svg>
                        </span>
                        @if ($post->media_type === 'VIDEO')<span class="absolute right-3 top-3 text-cream"><svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4v16l13-8z"/></svg></span>@endif
                    </a>
                @endforeach
            </div>
        @endif

        <div class="reveal mt-12 text-center">
            <a href="{{ setting('instagram_url', 'https://www.instagram.com/zamzamkairana/') }}" target="_blank" rel="noopener" class="btn btn-line">Follow on Instagram</a>
        </div>
    </div>
</section>

{{-- ================= Visit ================= --}}
<section class="lattice border-t border-gold/10">
    <div class="wrap grid gap-12 py-20 lg:grid-cols-2 lg:py-28">
        <div>
            @include('site.partials.heading', ['eyebrow' => 'Visit us', 'title' => 'Come hungry, <span class="gold-text">leave family</span>', 'align' => 'left'])
            <dl class="reveal mt-10 space-y-7" style="--d: 120ms">
                <div><dt class="eyebrow">Address</dt><dd class="mt-2 text-lg text-cream">{{ setting('address') }}</dd></div>
                <div><dt class="eyebrow">Hours</dt><dd class="mt-2 text-lg text-cream">{{ setting('opening_hours') }}</dd></div>
                @if (setting('phone'))<div><dt class="eyebrow">Phone</dt><dd class="mt-2 text-lg"><a class="text-gold-light hover:text-gold" href="tel:{{ setting('phone') }}">{{ setting('phone') }}</a></dd></div>@endif
            </dl>
            <a href="{{ route('contact') }}" class="link-gold reveal mt-10" style="--d: 200ms">Get in touch</a>
        </div>

        <div class="reveal min-h-80 border border-gold/20 bg-ink-3" style="--d: 150ms">
            @if (setting('map_embed_url'))
                <iframe src="{{ setting('map_embed_url') }}" title="Map to {{ setting('site_name') }}" class="size-full min-h-80 grayscale-[55%] contrast-[1.05]" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            @else
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(setting('site_name').' '.setting('address')) }}" target="_blank" rel="noopener" class="group grid size-full min-h-80 place-items-center p-8 text-center">
                    <span>
                        <svg class="mx-auto size-10 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M12 21s7-6.2 7-11.5A7 7 0 0 0 5 9.500C5 14.800 12 21 12 21Z"/><circle cx="12" cy="9.500" r="2.500"/></svg>
                        <span class="mt-5 block font-display text-xl tracking-widest text-cream">Open in Google Maps</span>
                        <span class="mt-2 block text-sand">{{ setting('address') }}</span>
                    </span>
                </a>
            @endif
        </div>
    </div>
</section>
@endsection
