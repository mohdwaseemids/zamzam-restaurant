@php
    $siteName = setting('site_name', 'Zam Zam Family Restaurant');
    $phone = setting('phone');
    $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp'));
    $ordering = (bool) setting('ordering_enabled', true);
    $nav = [
        'home' => 'Home',
        'menu' => 'Menu',
        'gallery' => 'Gallery',
        'stories' => 'Stories',
        'reserve' => 'Reserve',
        'contact' => 'Contact',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b0907">
    <title>@yield('title', $siteName.' — '.setting('tagline', 'Authentic Flavours • Family Dining'))</title>
    <meta name="description" content="@yield('description', setting('hero_subtitle', 'Family restaurant in Kairana serving mutton, chicken, fish, biryani and kebabs.'))">
    <meta property="og:title" content="{{ $siteName }}">
    <meta property="og:type" content="restaurant">
    <meta property="og:image" content="{{ media_url(setting('hero_image')) }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=cinzel:400,500,600|cormorant-garamond:400,400i,500i,600|jost:300,400,500|amiri:400&display=swap" rel="stylesheet">
    <script>
        window.ZZ = {
            csrf: @json(csrf_token()),
            cartCount: {{ (int) ($cartCount ?? 0) }},
            signedIn: @json(auth()->check()),
            favourites: @json(auth()->check() ? auth()->user()->favourites()->pluck('menu_items.id')->all() : []),
            routes: {
                cartOptions: @json(route('cart.options')),
                favourite: @json(route('favourites.toggle', '__ID__')),
                login: @json(route('login')),
                cart: @json(route('cart.show')),
                cartAdd: @json(route('cart.add')),
                cartUpdate: @json(route('cart.update')),
                cartCoupon: @json(route('cart.coupon')),
            },
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen overflow-x-hidden pb-16 lg:pb-0" x-data="{ scrolled: false, mobileNav: false }" @scroll.window.passive="scrolled = window.scrollY > 40" @keydown.escape.window="$store.cart.open = false; $store.viewer.close(); mobileNav = false">

    {{-- ================= Header ================= --}}
    <header class="fixed inset-x-0 top-0 z-40 transition-all duration-500"
            :class="scrolled || mobileNav ? 'bg-ink/92 backdrop-blur-md border-b border-gold/15 py-3' : 'py-5 @yield('header-class', 'bg-gradient-to-b from-ink/80 to-transparent')'">
        <div class="wrap flex items-center justify-between gap-6">
            <a href="{{ route('home') }}" class="group flex items-center gap-3" aria-label="{{ $siteName }} home">
                <span class="grid size-11 place-items-center rounded-full border border-gold/60 font-display text-sm tracking-widest text-gold transition-colors duration-500 group-hover:bg-gold group-hover:text-ink">ZZ</span>
                <span class="whitespace-nowrap leading-none">
                    <span class="block font-display text-lg tracking-[0.22em] text-gold-light">ZAM ZAM</span>
                    <span class="mt-1 block font-serif text-sm italic tracking-wide text-sand">Family Restaurant</span>
                </span>
            </a>

            <nav class="hidden items-center gap-8 xl:flex" aria-label="Main">
                @foreach ($nav as $route => $label)
                    <a href="{{ route($route) }}"
                       class="relative py-2 text-[0.72rem] font-medium uppercase tracking-[0.28em] transition-colors hover:text-gold {{ request()->routeIs($route) ? 'text-gold' : 'text-cream/80' }}">
                        {{ $label }}
                        @if (request()->routeIs($route))<span class="absolute inset-x-0 -bottom-0.5 mx-auto h-px w-5 bg-gold"></span>@endif
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ auth()->check() ? route('account') : route('login') }}" class="relative grid size-11 place-items-center border border-gold/40 text-gold-light transition-colors hover:bg-gold hover:text-ink" aria-label="{{ auth()->check() ? 'My account' : 'Sign in' }}" title="{{ auth()->check() ? 'My account' : 'Sign in' }}">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.6-3.6 3.4-5.5 7-5.5s6.400 1.900 7 5.500"/></svg>
                    @auth<span class="absolute -right-1 -top-1 size-2.5 rounded-full bg-leaf ring-2 ring-ink"></span>@endauth
                </a>
                @if ($ordering)
                    <button type="button" @click="$store.cart.open = true" class="relative hidden size-11 place-items-center border border-gold/40 text-gold-light transition-colors hover:bg-gold hover:text-ink sm:grid" aria-label="Open your order">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                        <span x-cloak x-show="$store.cart.data.count > 0" x-text="$store.cart.data.count" class="absolute -right-2 -top-2 grid min-w-5 place-items-center rounded-full bg-gold px-1 text-[0.65rem] font-medium leading-5 text-ink"></span>
                    </button>
                    <a href="{{ route('menu') }}" class="btn btn-gold btn-sm hidden sm:inline-flex">Order online</a>
                @endif
                <button type="button" class="grid size-11 place-items-center border border-gold/40 text-gold-light xl:hidden" @click="mobileNav = !mobileNav" :aria-expanded="mobileNav" aria-label="Menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path x-show="!mobileNav" d="M4 7h16M4 12h16M4 17h16"/><path x-cloak x-show="mobileNav" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </div>

        <nav x-cloak x-show="mobileNav" x-transition.opacity class="wrap mt-3 max-h-[75vh] overflow-y-auto border-t border-gold/15 pb-4 pt-2 xl:hidden" aria-label="Mobile">
            @foreach ($nav as $route => $label)
                <a href="{{ route($route) }}" class="block border-b border-white/5 py-4 font-display text-lg tracking-[0.2em] {{ request()->routeIs($route) ? 'text-gold' : 'text-cream' }}">{{ $label }}</a>
            @endforeach
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="block border-b border-white/5 py-4 font-display text-lg tracking-[0.2em] {{ request()->routeIs('account*', 'login', 'register') ? 'text-gold' : 'text-cream' }}">{{ auth()->check() ? 'My account' : 'Sign in' }}</a>
            <a href="{{ route('track') }}" class="block py-4 text-sm uppercase tracking-[0.28em] text-sand">Track my order</a>
        </nav>
    </header>

    {{-- ================= Flash messages ================= --}}
    @if (session('success') || session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition.opacity
             class="fixed inset-x-4 top-24 z-50 mx-auto flex max-w-xl items-start gap-4 border bg-ink-3 p-5 shadow-2xl {{ session('error') ? 'border-ruby/60' : 'border-gold/60' }}" role="status">
            <span class="mt-1.5 size-2 flex-none rotate-45 {{ session('error') ? 'bg-ruby' : 'bg-gold' }}"></span>
            <p class="flex-1 text-base text-cream">{{ session('success') ?? session('error') }}</p>
            <button type="button" @click="show = false" class="text-sand hover:text-gold" aria-label="Dismiss">✕</button>
        </div>
    @endif

    <main>@yield('content')</main>

    @include('site.partials.footer')
    @include('site.partials.overlays')

    {{-- ================= Mobile app-style bar ================= --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-gold/20 bg-ink/95 backdrop-blur-md lg:hidden" style="padding-bottom: env(safe-area-inset-bottom)" aria-label="Quick actions">
        @php
            $tabs = [
                ['home', 'Home', 'M4 11 12 4l8 7v9h-5v-6H9v6H4z'],
                ['menu', 'Menu', 'M5 4v7a2 2 0 0 0 4 0V4M7 11v9M17 4c-2 2-2 6 0 8v8'],
                ['reserve', 'Reserve', 'M5 6h14v14H5zM5 10h14M9 3v4M15 3v4'],
            ];
        @endphp
        @foreach ($tabs as [$route, $label, $path])
            <a href="{{ route($route) }}" class="flex flex-col items-center gap-1 py-2.5 text-[0.6rem] uppercase tracking-[0.18em] {{ request()->routeIs($route) ? 'text-gold' : 'text-sand' }}">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" stroke-linecap="round"><path d="{{ $path }}"/></svg>{{ $label }}
            </a>
        @endforeach
        @if ($ordering)
            <button type="button" @click="$store.cart.open = true" class="relative flex flex-col items-center gap-1 py-2.5 text-[0.6rem] uppercase tracking-[0.18em] text-sand">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>Order
                <span x-cloak x-show="$store.cart.data.count > 0" x-text="$store.cart.data.count" class="absolute right-[26%] top-1 grid min-w-4 place-items-center rounded-full bg-gold px-1 text-[0.6rem] font-medium leading-4 text-ink"></span>
            </button>
        @elseif ($phone)
            <a href="tel:{{ $phone }}" class="flex flex-col items-center gap-1 py-2.5 text-[0.6rem] uppercase tracking-[0.18em] text-sand">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A15 15 0 0 1 4 5a1 1 0 0 1 1-1Z"/></svg>Call
            </a>
        @else
            <a href="{{ route('contact') }}" class="flex flex-col items-center gap-1 py-2.5 text-[0.6rem] uppercase tracking-[0.18em] text-sand">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M4 6h16v12H4zM4 7l8 6 8-6"/></svg>Contact
            </a>
        @endif
    </nav>

    @if ($whatsapp)
        <a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('Assalamu alaikum, I would like to know more about Zam Zam Family Restaurant.') }}" target="_blank" rel="noopener"
           class="fixed bottom-20 right-4 z-30 grid size-13 place-items-center rounded-full bg-[#1faa53] text-white shadow-xl transition-transform hover:scale-110 lg:bottom-6 lg:right-6" aria-label="Chat on WhatsApp">
            <svg class="size-7" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.700 6.700 0 0 1-3.300-2.900c-.3-.4.300-.4.700-1.300.1-.2 0-.3 0-.5l-.800-1.800c-.200-.500-.400-.400-.600-.400h-.500c-.2 0-.4.1-.7.300-.2.300-.900.900-.900 2.200s.900 2.500 1.100 2.700c.1.2 1.900 2.900 4.500 4 .600.300 1.100.400 1.500.500.600.200 1.200.200 1.600.100.500-.1 1.500-.6 1.700-1.200.2-.600.2-1.100.200-1.200-.100-.100-.300-.200-.500-.300Z"/></svg>
        </a>
    @endif

    @stack('scripts')
</body>
</html>
