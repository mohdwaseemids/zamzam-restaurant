@extends('layouts.site')

@section('title', 'Reserve a table — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('content')
@include('site.partials.page-hero', [
    'eyebrow' => 'Reservations',
    'title' => 'Reserve <span class="gold-text">your table</span>',
    'lead' => 'Family dinners, birthdays and dawats — tell us when, we will take care of the rest.',
    'image' => media_url(setting('hero_image')),
])

<section class="wrap grid gap-14 pb-24 lg:grid-cols-5">
    <form method="POST" action="{{ route('reserve.store') }}" class="card reveal grid gap-6 p-6 md:grid-cols-2 md:p-10 lg:col-span-3">
        @csrf
        <div>
            <label class="field-label" for="name">Full name *</label>
            <input class="field" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="100" autocomplete="name">
            @error('name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="phone">Mobile number *</label>
            <input class="field" id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', auth()->user()?->phone) }}" required autocomplete="tel" placeholder="98xxxxxxxx">
            @error('phone')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="date">Date *</label>
            <input class="field" id="date" name="date" type="date" value="{{ old('date', now()->toDateString()) }}" min="{{ now()->toDateString() }}" max="{{ now()->addMonths(3)->subDay()->toDateString() }}" required>
            @error('date')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="time">Time *</label>
            <input class="field" id="time" name="time" type="time" value="{{ old('time', '20:00') }}" required>
            @error('time')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="guests">Guests *</label>
            <input class="field" id="guests" name="guests" type="number" inputmode="numeric" min="1" max="300" value="{{ old('guests', 4) }}" required>
            @error('guests')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field-label" for="occasion">Occasion</label>
            <select class="field" id="occasion" name="occasion">
                @foreach (['' => 'Just dining', 'Family dinner' => 'Family dinner', 'Birthday' => 'Birthday', 'Anniversary' => 'Anniversary', 'Party / Dawat' => 'Party / Dawat', 'Business' => 'Business meal'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('occasion') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="field-label" for="message">Special requests</label>
            <textarea class="field" id="message" name="message" rows="3" maxlength="1000" placeholder="High chair, cake, a quiet corner…">{{ old('message') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <button class="btn btn-gold w-full md:w-auto">Request my table</button>
            <p class="mt-3 text-sm text-sand">We will call you to confirm. Your table is booked only after our confirmation.</p>
        </div>
    </form>

    <aside class="reveal space-y-8 lg:col-span-2" style="--d: 120ms">
        <div class="arch zoom mx-auto aspect-[4/5] max-w-sm border border-gold/20">
            <img src="{{ media_url(setting('about_image'), 'Zam Zam') }}" alt="" loading="lazy">
        </div>
        <dl class="space-y-6 text-center">
            <div><dt class="eyebrow">Hours</dt><dd class="mt-2 font-serif text-xl italic text-cream">{{ setting('opening_hours') }}</dd></div>
            @if (setting('phone'))<div><dt class="eyebrow">Prefer to call?</dt><dd class="mt-2"><a href="tel:{{ setting('phone') }}" class="font-display text-2xl tracking-wider text-gold-light hover:text-gold">{{ setting('phone') }}</a></dd></div>@endif
        </dl>
    </aside>
</section>
@endsection
