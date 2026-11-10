@extends('site.account.layout')

@section('title', 'My orders — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('heading', 'My orders')

@section('account')
<div class="space-y-4">
    @forelse ($orders as $order)
        @include('site.account.partials.order-row', ['order' => $order])
    @empty
        <div class="card p-10 text-center">
            <div class="ornament justify-center"><span></span></div>
            <p class="mt-6 font-serif text-2xl italic text-cream">No orders yet</p>
            <p class="mt-2 text-sand">Orders you place while signed in will appear here.</p>
            <a href="{{ route('menu') }}" class="btn btn-gold mt-7">Browse the menu</a>
        </div>
    @endforelse
</div>

@if ($orders->hasPages())
    <nav class="mt-10 flex items-center justify-between gap-4" aria-label="Pages">
        @if ($orders->onFirstPage())<span></span>@else<a href="{{ $orders->previousPageUrl() }}" class="btn btn-line btn-sm">← Newer</a>@endif
        <span class="text-sm text-sand">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
        @if ($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}" class="btn btn-line btn-sm">Older →</a>@else<span></span>@endif
    </nav>
@endif
@endsection
