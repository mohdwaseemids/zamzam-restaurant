<x-filament-widgets::widget>
    <x-filament::section heading="Top dishes · last 30 days">
        @forelse ($dishes as $dish)
            <div class="zz-bar-row">
                <div class="zz-bar-head">
                    <span class="zz-bar-name">{{ $dish->name }}</span>
                    <span class="zz-bar-value">{{ (int) $dish->qty }} sold · {{ money($dish->revenue) }}</span>
                </div>
                <div class="zz-bar"><span style="width: {{ round(((int) $dish->qty / $max) * 100) }}%"></span></div>
            </div>
        @empty
            <p class="zz-muted">Dishes will appear here once orders start coming in.</p>
        @endforelse

        <div class="zz-divider"></div>

        <p class="zz-subhead">Today’s table bookings</p>
        @forelse ($bookings as $booking)
            <div class="zz-line">
                <span><strong>{{ $booking->time }}</strong> · {{ $booking->name }}</span>
                <span class="zz-muted">{{ $booking->guests }} guests · {{ ucfirst($booking->status) }}</span>
            </div>
        @empty
            <p class="zz-muted">No bookings for today.</p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>
