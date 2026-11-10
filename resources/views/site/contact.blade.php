@extends('layouts.site')

@section('title', 'Contact — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('content')
@php $whatsapp = preg_replace('/\D/', '', (string) setting('whatsapp')); @endphp

@include('site.partials.page-hero', [
    'eyebrow' => 'Contact',
    'title' => 'We would love to <span class="gold-text">host you</span>',
    'lead' => 'Questions, bulk orders or party bookings — send a message or just walk in.',
    'image' => media_url(setting('hero_image')),
])

<section class="wrap grid gap-14 pb-24 lg:grid-cols-2">
    <div class="reveal space-y-10">
        <dl class="grid gap-8 sm:grid-cols-2">
            <div><dt class="eyebrow">Address</dt><dd class="mt-3 text-lg text-cream">{{ setting('address') }}</dd></div>
            <div><dt class="eyebrow">Hours</dt><dd class="mt-3 text-lg text-cream">{{ setting('opening_hours') }}</dd></div>
            @if (setting('phone'))<div><dt class="eyebrow">Phone</dt><dd class="mt-3 text-lg"><a class="text-gold-light hover:text-gold" href="tel:{{ setting('phone') }}">{{ setting('phone') }}</a></dd></div>@endif
            @if ($whatsapp)<div><dt class="eyebrow">WhatsApp</dt><dd class="mt-3 text-lg"><a class="text-gold-light hover:text-gold" href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener">Chat with us</a></dd></div>@endif
            @if (setting('email'))<div><dt class="eyebrow">Email</dt><dd class="mt-3 text-lg"><a class="text-gold-light hover:text-gold" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></dd></div>@endif
        </dl>

        <div class="min-h-72 border border-gold/20 bg-ink-3">
            @if (setting('map_embed_url'))
                <iframe src="{{ setting('map_embed_url') }}" title="Map" class="size-full min-h-72 grayscale-[55%]" loading="lazy" allowfullscreen></iframe>
            @else
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(setting('site_name').' '.setting('address')) }}" target="_blank" rel="noopener" class="grid size-full min-h-72 place-items-center p-8 text-center">
                    <span>
                        <span class="block font-display text-xl tracking-widest text-cream">Open in Google Maps</span>
                        <span class="mt-2 block text-sand">{{ setting('address') }}</span>
                    </span>
                </a>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ route('contact.store') }}" class="card reveal grid content-start gap-6 p-6 md:grid-cols-2 md:p-10" style="--d: 120ms">
        @csrf
        <div>
            <label class="field-label" for="name">Name *</label>
            <input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name">
            @error('name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="phone">Mobile number *</label>
            <input class="field" id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" required autocomplete="tel">
            @error('phone')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2">
            <label class="field-label" for="subject">Subject</label>
            <input class="field" id="subject" name="subject" value="{{ old('subject') }}" maxlength="150" placeholder="Party booking, bulk order, feedback…">
        </div>
        <div class="md:col-span-2">
            <label class="field-label" for="message">Message *</label>
            <textarea class="field" id="message" name="message" rows="5" required maxlength="2000">{{ old('message') }}</textarea>
            @error('message')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-2"><button class="btn btn-gold w-full md:w-auto">Send message</button></div>
    </form>
</section>
@endsection
