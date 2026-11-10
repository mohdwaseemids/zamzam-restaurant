<x-filament-panels::page>
    <x-filament::section>
        <div class="zz-toolbar">
            <div class="zz-presets">
                @foreach (['today' => 'Today', 'yesterday' => 'Yesterday', 'week' => 'Last 7 days', 'month' => 'This month', 'last_month' => 'Last month'] as $key => $label)
                    <x-filament::button size="sm" color="gray" wire:click="setRange('{{ $key }}')">{{ $label }}</x-filament::button>
                @endforeach
            </div>
            <div class="zz-dates">
                <x-filament::input.wrapper prefix="From"><x-filament::input type="date" wire:model.live="from" /></x-filament::input.wrapper>
                <x-filament::input.wrapper prefix="To"><x-filament::input type="date" wire:model.live="to" /></x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    <div class="zz-stats">
        @foreach ($stats as $label => $value)
            <div class="zz-stat"><p class="zz-stat-label">{{ $label }}</p><p class="zz-stat-value">{{ $value }}</p></div>
        @endforeach
    </div>

    <div class="zz-grid-2">
        <x-filament::section heading="By order type">
            <div class="zz-table-wrap"><table class="zz-table">
                <thead><tr><th>Type</th><th class="zz-num">Orders</th><th class="zz-num">Sales</th></tr></thead>
                <tbody>
                    @forelse ($byType as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="zz-num">{{ $row['orders'] }}</td><td class="zz-num"><strong>{{ money($row['revenue']) }}</strong></td></tr>
                    @empty
                        <tr><td colspan="3" class="zz-empty">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-filament::section>

        <x-filament::section heading="By payment method">
            <div class="zz-table-wrap"><table class="zz-table">
                <thead><tr><th>Method</th><th class="zz-num">Orders</th><th class="zz-num">Sales</th></tr></thead>
                <tbody>
                    @forelse ($byPayment as $row)
                        <tr><td>{{ $row['label'] }}</td><td class="zz-num">{{ $row['orders'] }}</td><td class="zz-num"><strong>{{ money($row['revenue']) }}</strong></td></tr>
                    @empty
                        <tr><td colspan="3" class="zz-empty">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-filament::section>

        <x-filament::section heading="Best selling dishes">
            <div class="zz-table-wrap"><table class="zz-table">
                <thead><tr><th>Dish</th><th class="zz-num">Qty</th><th class="zz-num">Sales</th></tr></thead>
                <tbody>
                    @forelse ($dishes as $dish)
                        <tr><td>{{ $dish->name }}</td><td class="zz-num">{{ (int) $dish->qty }}</td><td class="zz-num"><strong>{{ money($dish->revenue) }}</strong></td></tr>
                    @empty
                        <tr><td colspan="3" class="zz-empty">No dishes sold in this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-filament::section>

        <x-filament::section heading="Day by day">
            <div class="zz-table-wrap"><table class="zz-table">
                <thead><tr><th>Date</th><th class="zz-num">Orders</th><th class="zz-num">Sales</th></tr></thead>
                <tbody>
                    @forelse ($daily as $day)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($day->day)->format('D, d M Y') }}</td><td class="zz-num">{{ $day->orders_count }}</td><td class="zz-num"><strong>{{ money($day->revenue) }}</strong></td></tr>
                    @empty
                        <tr><td colspan="3" class="zz-empty">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
