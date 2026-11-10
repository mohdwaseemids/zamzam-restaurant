@extends('layouts.site')

@section('title', 'Videos & Reviews — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('content')
@include('site.partials.page-hero', [
    'eyebrow' => 'Stories',
    'title' => 'Live from <span class="gold-text">our tables</span>',
    'lead' => 'Watch our guests taste the food — and read what they said after.',
    'image' => media_url(setting('about_image')),
])

{{-- ================= Videos ================= --}}
<section class="wrap pb-20" x-data="{ filter: 'all' }">
    @if ($videos->isEmpty())
        <div class="card mx-auto max-w-2xl p-10 text-center">
            <span class="play mx-auto"><svg class="ml-1 size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M7 4v16l13-8z"/></svg></span>
            <p class="mt-6 font-serif text-2xl italic text-cream">Guest videos are coming soon</p>
            <p class="mt-2 text-sand">Until then, catch our latest reels on Instagram.</p>
            <a href="{{ setting('instagram_url', 'https://www.instagram.com/zamzamkairana/') }}" target="_blank" rel="noopener" class="btn btn-line mt-7">Watch on Instagram</a>
        </div>
    @else
        @php $usedCategories = $videos->pluck('category')->unique(); @endphp
        <div class="scrollbar-none -mx-5 flex justify-start gap-2 overflow-x-auto px-5 md:justify-center">
            <button type="button" @click="filter = 'all'" class="flex-none border px-5 py-2.5 text-[0.68rem] font-medium uppercase tracking-[0.22em]" :class="filter === 'all' ? 'border-gold bg-gold text-ink' : 'border-gold/25 text-cream/80 hover:border-gold'">All videos</button>
            @foreach ($videoCategories as $key => $label)
                @if ($usedCategories->contains($key))
                    <button type="button" @click="filter = '{{ $key }}'" class="flex-none border px-5 py-2.5 text-[0.68rem] font-medium uppercase tracking-[0.22em]" :class="filter === '{{ $key }}' ? 'border-gold bg-gold text-ink' : 'border-gold/25 text-cream/80 hover:border-gold'">{{ $label }}</button>
                @endif
            @endforeach
        </div>

        <div class="mt-12 grid grid-cols-2 gap-4 md:grid-cols-3 md:gap-6 lg:grid-cols-4">
            @foreach ($videos as $video)
                <div x-show="filter === 'all' || filter === '{{ $video->category }}'">
                    @include('site.partials.video-card', ['video' => $video, 'delay' => 0])
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- ================= Reviews ================= --}}
<section class="section lattice border-y border-gold/10">
    <div class="wrap">
        @include('site.partials.heading', [
            'eyebrow' => 'Guest book',
            'title' => $rating['count'] ? '<span class="gold-text">'.$rating['average'].' ★</span> from '.$rating['count'].' '.Str::plural('guest', $rating['count']) : 'Be the first to write',
        ])

        @if ($reviews->isEmpty())
            <p class="reveal mt-10 text-center font-serif text-xl italic text-sand">No reviews yet — eaten with us? Tell everyone how it was.</p>
        @else
            <div class="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $review)
                    @include('site.partials.review-card', ['review' => $review, 'delay' => ($loop->index % 3) * 80])
                @endforeach
            </div>
            <div class="mt-12">{{ $reviews->fragment('reviews')->links() }}</div>
        @endif
    </div>
</section>

{{-- ================= Share your experience ================= --}}
<section id="share" class="section">
    <div class="wrap grid gap-14 lg:grid-cols-5">
        <div class="lg:col-span-2">
            @include('site.partials.heading', ['eyebrow' => 'Your turn', 'title' => 'Share your experience', 'lead' => 'Your words help other families decide — and help our kitchen get better every day.', 'align' => 'left'])
            @if (setting('google_review_url'))
                <a href="{{ setting('google_review_url') }}" target="_blank" rel="noopener" class="link-gold reveal mt-8">Or review us on Google</a>
            @endif
        </div>

        <form method="POST" action="{{ route('reviews.store') }}" enctype="multipart/form-data" class="card reveal grid gap-6 p-6 md:grid-cols-2 md:p-10 lg:col-span-3" x-data="{ rating: {{ (int) old('rating', 5) }}, hover: 0 }">
            @csrf
            <div class="md:col-span-2">
                <span class="field-label">Your rating</span>
                <div class="flex gap-1" @mouseleave="hover = 0">
                    @for ($star = 1; $star <= 5; $star++)
                        <button type="button" @click="rating = {{ $star }}" @mouseenter="hover = {{ $star }}" class="text-4xl leading-none transition-colors" :class="(hover || rating) >= {{ $star }} ? 'text-gold' : 'text-white/15'" aria-label="{{ $star }} {{ Str::plural('star', $star) }}">★</button>
                    @endfor
                </div>
                <input type="hidden" name="rating" :value="rating">
                @error('rating')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="field-label" for="r-name">Your name *</label>
                <input class="field" id="r-name" name="customer_name" value="{{ old('customer_name', auth()->user()?->name) }}" required maxlength="100" autocomplete="name">
                @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="r-city">City</label>
                <input class="field" id="r-city" name="city" value="{{ old('city') }}" maxlength="100" placeholder="Kairana">
            </div>
            <div class="md:col-span-2">
                <label class="field-label" for="r-dish">Which dish did you love?</label>
                <select class="field" id="r-dish" name="menu_item_id">
                    <option value="">— Overall experience —</option>
                    @foreach ($dishes as $dish)
                        <option value="{{ $dish->id }}" @selected(old('menu_item_id') == $dish->id)>{{ $dish->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="field-label" for="r-comment">Your review *</label>
                <textarea class="field" id="r-comment" name="comment" rows="4" required minlength="10" maxlength="1000" placeholder="Tell us about the food, the service, the evening…">{{ old('comment') }}</textarea>
                @error('comment')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label class="field-label" for="r-photo">Add a photo (optional)</label>
                <input class="field file:mr-4 file:border-0 file:bg-gold file:px-4 file:py-2 file:text-xs file:font-medium file:uppercase file:tracking-widest file:text-ink" id="r-photo" type="file" name="photo" accept="image/*">
                @error('photo')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <button class="btn btn-gold w-full md:w-auto">Submit review</button>
                <p class="mt-3 text-sm text-sand">Reviews appear on the website after a quick check by our team.</p>
            </div>
        </form>
    </div>
</section>
@endsection
