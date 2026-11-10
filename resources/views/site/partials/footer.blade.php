@php
    $phone = setting('phone');
    $instagram = setting('instagram_url');
    $socials = array_filter([
        'Instagram' => $instagram,
        'Facebook' => setting('facebook_url'),
        'YouTube' => setting('youtube_url'),
    ]);
@endphp
<footer class="lattice border-t border-gold/15">
    <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-4 lg:py-20">
        <div class="lg:col-span-1">
            <p class="font-display text-2xl tracking-[0.22em] text-gold-light">ZAM ZAM</p>
            <p class="mt-1 font-serif text-lg italic text-sand">Family Restaurant</p>
            <p class="mt-5 max-w-xs text-sand">{{ setting('tagline', 'Authentic Flavours • Family Dining') }}</p>
            <p class="mt-5 font-arabic text-2xl text-gold/80" lang="ar" dir="rtl">أهلاً وسهلاً</p>
        </div>

        <div>
            <p class="eyebrow">Explore</p>
            <ul class="mt-5 space-y-3 text-cream/85">
                <li><a class="transition-colors hover:text-gold" href="{{ route('menu') }}">Our menu</a></li>
                <li><a class="transition-colors hover:text-gold" href="{{ route('gallery') }}">Gallery</a></li>
                <li><a class="transition-colors hover:text-gold" href="{{ route('stories') }}">Videos &amp; reviews</a></li>
                <li><a class="transition-colors hover:text-gold" href="{{ route('reserve') }}">Reserve a table</a></li>
                <li><a class="transition-colors hover:text-gold" href="{{ route('track') }}">Track my order</a></li>
                <li><a class="transition-colors hover:text-gold" href="{{ auth()->check() ? route('account') : route('login') }}">{{ auth()->check() ? 'My account' : 'Sign in / Create account' }}</a></li>
            </ul>
        </div>

        <div>
            <p class="eyebrow">Visit us</p>
            <address class="mt-5 space-y-3 not-italic text-cream/85">
                <p>{{ setting('address', 'Kairana, Uttar Pradesh') }}</p>
                <p class="text-sand">{{ setting('opening_hours', 'Open every day') }}</p>
                @if ($phone)<p><a class="text-gold-light hover:text-gold" href="tel:{{ $phone }}">{{ $phone }}</a></p>@endif
                @if (setting('email'))<p><a class="hover:text-gold" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></p>@endif
            </address>
        </div>

        <div>
            <p class="eyebrow">Follow</p>
            <ul class="mt-5 space-y-3 text-cream/85">
                @foreach ($socials as $label => $url)
                    <li><a class="transition-colors hover:text-gold" href="{{ $url }}" target="_blank" rel="noopener">{{ $label }} @if ($label === 'Instagram' && setting('instagram_handle'))<span class="text-sand">· {{ '@'.setting('instagram_handle') }}</span>@endif</a></li>
                @endforeach
                @if (setting('google_review_url'))
                    <li><a class="transition-colors hover:text-gold" href="{{ setting('google_review_url') }}" target="_blank" rel="noopener">Review us on Google</a></li>
                @endif
            </ul>
        </div>
    </div>

    <div class="border-t border-white/5">
        <div class="wrap flex flex-col items-center justify-between gap-3 py-6 text-sm text-sand md:flex-row">
            <p>© {{ date('Y') }} {{ setting('site_name', 'Zam Zam Family Restaurant') }}. All rights reserved.</p>
            <p class="tracking-[0.25em] uppercase text-xs">100% Halal · Made fresh daily</p>
        </div>
    </div>
</footer>
