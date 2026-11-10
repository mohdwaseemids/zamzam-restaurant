<x-filament-panels::page>
    <x-filament::section>
        <div class="zz-toolbar">
            <p class="zz-muted">{{ number_format($totalCustomers) }} {{ Str::plural('customer', $totalCustomers) }} have ordered so far. Sorted by total spend.</p>
            <div class="zz-search">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input type="search" wire:model.live.debounce.400ms="search" placeholder="Search name or mobile…" />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div class="zz-table-wrap">
            <table class="zz-table">
                <thead>
                    <tr><th>Customer</th><th class="zz-num">Orders</th><th class="zz-num">Total spent</th><th class="zz-hide-sm">Last order</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        @php $digits = preg_replace('/\D/', '', $customer->phone); @endphp
                        <tr wire:key="customer-{{ $customer->phone }}">
                            <td><strong>{{ $customer->name }}</strong><br><span class="zz-muted">{{ $customer->phone }}</span></td>
                            <td class="zz-num">{{ $customer->orders_count }}</td>
                            <td class="zz-num"><strong>{{ money($customer->spent) }}</strong></td>
                            <td class="zz-hide-sm zz-muted">{{ \Illuminate\Support\Carbon::parse($customer->last_order_at)->diffForHumans() }}</td>
                            <td class="zz-actions">
                                <a href="tel:{{ $customer->phone }}" class="zz-link">Call</a>
                                @if (strlen($digits) >= 10)
                                    <a href="https://wa.me/{{ strlen($digits) === 10 ? '91'.$digits : $digits }}" target="_blank" rel="noopener" class="zz-link">WhatsApp</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="zz-empty">{{ $search !== '' ? 'No customer matches your search.' : 'Customers appear here after their first order.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="zz-pagination"><x-filament::pagination :paginator="$customers" /></div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
