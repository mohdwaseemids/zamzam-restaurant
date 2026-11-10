{{-- Expects $video (Video). --}}
@php
    $payload = [
        'title' => $video->title,
        'embed' => $video->embed_url,
        'file' => $video->file_url,
        'vertical' => $video->type === 'instagram' || str_contains((string) $video->url, '/shorts/'),
    ];
@endphp
<button type="button" class="group reveal block w-full text-left" style="--d: {{ $delay ?? 0 }}ms"
        @click="$store.viewer.video(@js($payload))" aria-label="Play video: {{ $video->title }}">
    <div class="zoom relative aspect-[4/5] border border-gold/15 bg-ink-3">
        <img src="{{ $video->thumbnail_url }}" alt="" loading="lazy" class="size-full object-cover opacity-85">
        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/20 to-transparent"></div>

        <div class="absolute left-3 top-3 flex items-center gap-2 bg-ink/70 px-3 py-1.5 text-[0.62rem] font-medium uppercase tracking-[0.22em] text-cream backdrop-blur">
            @if ($video->category === 'live')<span class="live-dot"></span>@endif
            {{ \App\Models\Video::CATEGORIES[$video->category] ?? 'Video' }}
        </div>

        <div class="absolute inset-0 grid place-items-center">
            <span class="play"><svg class="ml-1 size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4v16l13-8z"/></svg></span>
        </div>

        <div class="absolute inset-x-0 bottom-0 p-5">
            <p class="font-display text-lg leading-snug tracking-wider text-cream">{{ $video->title }}</p>
            @if ($video->customer_name)<p class="mt-1 font-serif text-lg italic text-gold-light">— {{ $video->customer_name }}</p>@endif
        </div>
    </div>
</button>
