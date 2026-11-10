{{-- Expects $review (Review). --}}
<figure class="card reveal flex h-full flex-col p-7" style="--d: {{ $delay ?? 0 }}ms">
    <div class="flex items-center justify-between">
        <p class="tracking-[0.2em] text-gold" aria-label="{{ $review->rating }} out of 5 stars">{{ str_repeat('★', $review->rating) }}<span class="text-white/15">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
        <span class="font-display text-5xl leading-none text-gold/25" aria-hidden="true">”</span>
    </div>

    <blockquote class="mt-4 flex-1 font-serif text-xl italic leading-relaxed text-cream/90">{{ $review->comment }}</blockquote>

    @if ($review->photo_url)
        <img src="{{ $review->photo_url }}" alt="Photo shared by {{ $review->customer_name }}" loading="lazy" class="mt-5 aspect-video w-full object-cover">
    @endif

    <figcaption class="mt-6 flex items-center gap-4 border-t border-white/5 pt-5">
        <span class="grid size-11 flex-none place-items-center rounded-full border border-gold/50 font-display text-sm text-gold">{{ $review->initials }}</span>
        <span>
            <span class="block font-medium text-cream">{{ $review->customer_name }}</span>
            <span class="block text-sm text-sand">{{ collect([$review->city, $review->menuItem?->name])->filter()->implode(' · ') ?: $review->created_at->format('M Y') }}</span>
        </span>
    </figcaption>
</figure>
