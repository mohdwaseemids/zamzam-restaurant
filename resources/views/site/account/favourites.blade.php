@extends('site.account.layout')

@section('title', 'Favourites & points — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('heading', 'Favourites & points')

@section('account')
@if ($loyaltyEnabled)
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card p-6 text-center md:p-8">
            <p class="eyebrow">Loyalty points</p>
            <p class="mt-4 font-display text-5xl text-gold-light">{{ number_format($points) }}</p>
            <p class="mt-2 text-sand">Worth {{ money($points * $pointValue) }} on your next order</p>
            @if ($points < $minRedeem)
                <p class="mt-4 text-sm text-[#e5c07f]">Collect {{ $minRedeem - $points }} more to start using them.</p>
            @endif
        </div>

        <div class="card p-6 md:p-8 lg:col-span-2">
            <p class="eyebrow">How it works</p>
            <ul class="mt-4 space-y-2 text-cream/85">
                <li>Earn {{ rtrim(rtrim(number_format($earnRate, 2), '0'), '.') }} points for every ₹100 you spend, added when your order is completed.</li>
                <li>Each point is worth {{ money($pointValue) }}. Use them at checkout once you have {{ $minRedeem }} or more.</li>
                <li>Points are earned on orders placed while signed in.</li>
            </ul>

            @if ($history->isNotEmpty())
                <ul class="mt-6 divide-y divide-white/5 border-t border-gold/15">
                    @foreach ($history as $entry)
                        <li class="flex items-baseline justify-between gap-4 py-3">
                            <span class="text-cream/85">{{ $entry->description }} <span class="text-sm text-sand">· {{ $entry->created_at->format('d M Y') }}</span></span>
                            <span class="flex-none font-medium {{ $entry->points > 0 ? 'text-leaf' : 'text-sand' }}">{{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif

<h2 class="mt-12 font-display text-xl tracking-widest text-gold-light">Your favourite dishes</h2>

@if ($dishes->isEmpty())
    <div class="card mt-6 p-10 text-center">
        <div class="ornament justify-center"><span></span></div>
        <p class="mt-6 font-serif text-2xl italic text-cream">No favourites yet</p>
        <p class="mt-2 text-sand">Tap the heart on any dish to keep it here.</p>
        <a href="{{ route('menu') }}" class="btn btn-gold mt-7">Browse the menu</a>
    </div>
@else
    <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($dishes as $dish)
            @include('site.partials.dish-card', ['item' => $dish, 'delay' => ($loop->index % 4) * 70])
        @endforeach
    </div>
@endif
@endsection
