@extends('layouts.site')

@section('title', 'Track your order — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('header-class', 'bg-ink')

@section('content')
<section class="wrap grid min-h-svh place-items-center pb-24 pt-36">
    <form method="POST" action="{{ route('track.find') }}" class="card w-full max-w-lg p-8 md:p-12">
        @csrf
        <div class="text-center">
            <p class="eyebrow">Track order</p>
            <h1 class="display mt-4 text-3xl text-cream md:text-4xl">Where is my food?</h1>
            <div class="ornament mt-6 justify-center"><span></span></div>
        </div>

        <div class="mt-10 space-y-6">
            <div>
                <label class="field-label" for="order_number">Order number</label>
                <input class="field uppercase" id="order_number" name="order_number" value="{{ old('order_number') }}" required maxlength="30" placeholder="ZZ…" autocapitalize="characters">
                @error('order_number')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="phone">Mobile number used for the order</label>
                <input class="field" id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}" required maxlength="20">
                @error('phone')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-gold w-full">Track order</button>
        </div>
    </form>
</section>
@endsection
