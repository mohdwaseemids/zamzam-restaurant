@extends('layouts.site')

@section('header-class', 'bg-ink')

@push('head')<meta name="robots" content="noindex">@endpush

@section('content')
@php
    $me = auth()->user();
    $tabs = ['account' => 'Overview', 'account.orders' => 'My orders', 'account.settings' => 'Profile & addresses'];
@endphp
<section class="wrap pb-24 pt-32 lg:pt-36">
    <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
        <div class="flex items-center gap-5">
            <span class="grid size-16 flex-none place-items-center rounded-full border border-gold/60 font-display text-xl text-gold">{{ collect(explode(' ', $me->name))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('') }}</span>
            <div>
                <p class="eyebrow">My account</p>
                <h1 class="display mt-2 text-3xl text-cream md:text-4xl">@yield('heading', 'Salaam, '.Str::before($me->name, ' '))</h1>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-xs font-medium uppercase tracking-[0.24em] text-sand transition-colors hover:text-gold">Sign out</button>
        </form>
    </div>

    <nav class="scrollbar-none -mx-5 mt-8 flex gap-2 overflow-x-auto border-b border-gold/15 px-5 md:mx-0 md:px-0" aria-label="Account">
        @foreach ($tabs as $route => $label)
            <a href="{{ route($route) }}" class="-mb-px flex-none border-b-2 px-4 py-3 text-[0.72rem] font-medium uppercase tracking-[0.22em] transition-colors {{ request()->routeIs($route) ? 'border-gold text-gold' : 'border-transparent text-cream/70 hover:text-gold' }}">{{ $label }}</a>
        @endforeach
        @if ($me->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
            <a href="{{ url('/admin') }}" class="-mb-px flex-none border-b-2 border-transparent px-4 py-3 text-[0.72rem] font-medium uppercase tracking-[0.22em] text-cream/70 hover:text-gold">Admin panel ↗</a>
        @endif
    </nav>

    <div class="mt-10">@yield('account')</div>
</section>
@endsection
