@extends('layouts.site')

@section('header-class', 'bg-ink')

@push('head')<meta name="robots" content="noindex">@endpush

@section('content')
<section class="lattice">
    <div class="wrap grid min-h-svh items-center gap-12 pb-24 pt-32 lg:grid-cols-2 lg:gap-20">
        {{-- Brand side --}}
        <div class="hidden lg:block">
            <div class="arch-outline mx-auto max-w-sm">
                <div class="arch zoom aspect-[3/4]">
                    <img src="{{ media_url(setting('about_image'), 'Zam Zam') }}" alt="">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/20 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-8 text-center">
                        <p class="font-arabic text-2xl text-gold" lang="ar" dir="rtl">أهلاً وسهلاً</p>
                        <p class="mt-2 font-serif text-xl italic text-cream">Your table, your favourites,<br>one tap away.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form side --}}
        <div class="mx-auto w-full max-w-md">
            <p class="eyebrow rise">@yield('eyebrow', 'My account')</p>
            <h1 class="display rise mt-4 text-3xl text-cream md:text-4xl" style="--d: 100ms">@yield('heading')</h1>
            <div class="ornament rise mt-6" style="--d: 180ms"><span></span></div>
            @hasSection('lead')<p class="rise mt-5 text-sand" style="--d: 240ms">@yield('lead')</p>@endif

            <div class="card rise mt-8 p-6 md:p-8" style="--d: 300ms">
                @yield('form')
            </div>

            <div class="mt-6 text-center text-sand">@yield('below')</div>
        </div>
    </div>
</section>
@endsection
