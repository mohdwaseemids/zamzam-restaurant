{{-- Section heading. $eyebrow, $title, optional $lead, optional $align ('left') --}}
@php $left = ($align ?? 'center') === 'left'; @endphp
<div class="reveal {{ $left ? '' : 'mx-auto text-center' }} max-w-2xl">
    <p class="eyebrow">{{ $eyebrow }}</p>
    <h2 class="display mt-5 text-3xl text-cream md:text-5xl">{!! $title !!}</h2>
    <div class="ornament mt-6 {{ $left ? '' : 'justify-center' }}"><span></span></div>
    @isset($lead)<p class="lead mt-6">{{ $lead }}</p>@endisset
</div>
