{{-- Inner page banner. $eyebrow, $title, optional $lead, optional $image --}}
<section class="relative isolate overflow-hidden pb-16 pt-40 lg:pb-24 lg:pt-52">
    @isset($image)
        <img src="{{ $image }}" alt="" class="kenburns absolute inset-0 -z-20 size-full object-cover opacity-45">
    @endisset
    <div class="absolute inset-0 -z-10 bg-gradient-to-b from-ink/70 via-ink/75 to-ink"></div>
    <div class="wrap text-center">
        <p class="eyebrow rise">{{ $eyebrow }}</p>
        <h1 class="display rise mx-auto mt-5 max-w-4xl text-4xl text-cream md:text-6xl" style="--d: 120ms">{!! $title !!}</h1>
        <div class="ornament rise mt-7 justify-center" style="--d: 220ms"><span></span></div>
        @isset($lead)<p class="lead rise mx-auto mt-6 max-w-2xl" style="--d: 300ms">{{ $lead }}</p>@endisset
    </div>
</section>
