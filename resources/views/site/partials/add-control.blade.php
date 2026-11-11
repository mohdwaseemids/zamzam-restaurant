{{-- "Add" button that turns into a quantity stepper. Expects $item (MenuItem). --}}
@if (setting('ordering_enabled', true))
    @if ($item->is_available)
        <div>
            <button type="button" x-show="$store.cart.qty({{ $item->id }}) === 0" @click="$store.cart.add({{ $item->id }}, @js($item->name))" class="btn btn-line btn-sm">Add <span aria-hidden="true">+</span></button>
            <div x-cloak x-show="$store.cart.qty({{ $item->id }}) > 0" class="stepper">
                <button type="button" @click="$store.cart.set({{ $item->id }}, $store.cart.qty({{ $item->id }}) - 1)" aria-label="Decrease {{ $item->name }}">−</button>
                <span x-text="$store.cart.qty({{ $item->id }})"></span>
                <button type="button" @click="$store.cart.set({{ $item->id }}, $store.cart.qty({{ $item->id }}) + 1)" aria-label="Increase {{ $item->name }}">+</button>
            </div>
        </div>
    @else
        <span class="border border-white/15 px-3 py-2 text-[0.65rem] font-medium uppercase tracking-[0.2em] text-sand">Sold out today</span>
    @endif
@endif
