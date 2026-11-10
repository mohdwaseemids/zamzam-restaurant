@extends('layouts.site')

@section('title', 'Gallery — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('content')
@php
    $payload = $images->map(fn ($g) => ['src' => $g->image_url, 'title' => $g->title, 'category' => $g->category])->values();
    $used = $images->pluck('category')->unique();
@endphp

@include('site.partials.page-hero', [
    'eyebrow' => 'Gallery',
    'title' => 'Moments at <span class="gold-text">Zam Zam</span>',
    'lead' => 'The food, the fire and the families that fill our tables.',
    'image' => $images->firstWhere('category', 'ambience')?->image_url ?? media_url(setting('hero_image')),
])

<section class="wrap pb-24" x-data="{ filter: 'all', all: @js($payload), get shown() { return this.filter === 'all' ? this.all : this.all.filter(i => i.category === this.filter) } }">
    @if ($images->isEmpty())
        <p class="py-20 text-center font-serif text-2xl italic text-sand">Photos are coming soon.</p>
    @else
        <div class="scrollbar-none -mx-5 flex justify-start gap-2 overflow-x-auto px-5 md:justify-center">
            <button type="button" @click="filter = 'all'" class="flex-none border px-5 py-2.5 text-[0.68rem] font-medium uppercase tracking-[0.22em] transition-colors" :class="filter === 'all' ? 'border-gold bg-gold text-ink' : 'border-gold/25 text-cream/80 hover:border-gold'">All</button>
            @foreach ($categories as $key => $label)
                @if ($used->contains($key))
                    <button type="button" @click="filter = '{{ $key }}'" class="flex-none border px-5 py-2.5 text-[0.68rem] font-medium uppercase tracking-[0.22em] transition-colors" :class="filter === '{{ $key }}' ? 'border-gold bg-gold text-ink' : 'border-gold/25 text-cream/80 hover:border-gold'">{{ $label }}</button>
                @endif
            @endforeach
        </div>

        <div class="mt-12 columns-2 gap-3 md:columns-3 md:gap-4 xl:columns-4">
            <template x-for="(image, index) in shown" :key="image.src">
                <button type="button" class="group zoom relative mb-3 block w-full break-inside-avoid md:mb-4" @click="$store.viewer.photos(shown, index)" :aria-label="'Open photo: ' + (image.title || '')">
                    <img :src="image.src" :alt="image.title" loading="lazy" class="w-full bg-ink-3 object-cover" :class="index % 3 === 0 ? 'aspect-[3/4]' : (index % 3 === 1 ? 'aspect-square' : 'aspect-[4/3]')">
                    <span class="absolute inset-0 flex items-end bg-gradient-to-t from-ink/90 via-transparent to-transparent p-4 opacity-0 transition-opacity duration-500 group-hover:opacity-100">
                        <span class="font-serif text-lg italic text-cream" x-text="image.title"></span>
                    </span>
                </button>
            </template>
        </div>
    @endif
</section>

@if ($instagram->isNotEmpty())
<section class="section lattice border-t border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', ['eyebrow' => 'Instagram', 'title' => '@'.e(setting('instagram_handle', 'zamzamkairana'))])
        <div class="mt-12 grid grid-cols-3 gap-2 md:grid-cols-6 md:gap-3">
            @foreach ($instagram as $post)
                <a href="{{ $post->permalink ?: setting('instagram_url') }}" target="_blank" rel="noopener" class="zoom reveal block aspect-square" aria-label="Open Instagram post">
                    <img src="{{ $post->image_url }}" alt="{{ Str::limit($post->caption, 80) }}" loading="lazy" class="size-full object-cover">
                </a>
            @endforeach
        </div>
        <div class="reveal mt-10 text-center"><a href="{{ setting('instagram_url') }}" target="_blank" rel="noopener" class="btn btn-line">Follow on Instagram</a></div>
    </div>
</section>
@endif
@endsection
