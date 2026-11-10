@extends('site.account.layout')

@section('title', 'Profile & addresses — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('heading', 'Profile & addresses')

@section('account')
<div class="grid gap-10 lg:grid-cols-2">
    <div class="space-y-10">
        {{-- Profile --}}
        <form method="POST" action="{{ route('account.profile') }}" class="card space-y-6 p-6 md:p-8">
            @csrf @method('PUT')
            <h2 class="font-display text-xl tracking-widest text-gold-light">Your details</h2>
            <div>
                <label class="field-label" for="name">Full name</label>
                <input class="field" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name">
                @error('name', 'profile')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="phone">Mobile number</label>
                <input class="field" id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone', $user->phone) }}" required maxlength="14" autocomplete="tel-national">
                @error('phone', 'profile')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="email">Email</label>
                <input class="field" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="150" autocomplete="email" placeholder="Needed to reset a forgotten password">
                @error('email', 'profile')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-gold w-full sm:w-auto">Save details</button>
        </form>

        {{-- Password --}}
        <form method="POST" action="{{ route('account.password') }}" class="card space-y-6 p-6 md:p-8">
            @csrf @method('PUT')
            <h2 class="font-display text-xl tracking-widest text-gold-light">Change password</h2>
            <div>
                <label class="field-label" for="current_password">Current password</label>
                <input class="field" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                @error('current_password', 'password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="new_password">New password <span class="normal-case tracking-normal text-sand/70">(at least 8 characters)</span></label>
                <input class="field" id="new_password" name="password" type="password" required minlength="8" autocomplete="new-password">
                @error('password', 'password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="password_confirmation">Confirm new password</label>
                <input class="field" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
            </div>
            <button class="btn btn-line w-full sm:w-auto">Change password</button>
        </form>
    </div>

    {{-- Addresses --}}
    <div class="card p-6 md:p-8">
        <h2 class="font-display text-xl tracking-widest text-gold-light">Delivery addresses</h2>

        <ul class="mt-6 space-y-4">
            @forelse ($addresses as $address)
                <li class="border p-4 {{ $address->is_default ? 'border-gold/60 bg-gold/5' : 'border-white/10' }}">
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-gold">{{ $address->label }}@if ($address->is_default) · Default @endif</p>
                        <div class="flex flex-none gap-4 text-xs uppercase tracking-[0.18em]">
                            @unless ($address->is_default)
                                <form method="POST" action="{{ route('account.addresses.default', $address) }}">@csrf @method('PUT')<button class="text-sand hover:text-gold">Make default</button></form>
                            @endunless
                            <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Remove this address?')">@csrf @method('DELETE')<button class="text-sand hover:text-[#e58b7f]">Remove</button></form>
                        </div>
                    </div>
                    <p class="mt-2 text-cream">{{ $address->address }}</p>
                    @if ($address->landmark)<p class="text-sm text-sand">Near {{ $address->landmark }}</p>@endif
                </li>
            @empty
                <li class="text-sand">No address saved yet. Add one below and checkout will fill it in for you.</li>
            @endforelse
        </ul>

        <form method="POST" action="{{ route('account.addresses.store') }}" class="mt-8 space-y-5 border-t border-gold/15 pt-7">
            @csrf
            <p class="eyebrow">Add an address</p>
            <div>
                <label class="field-label" for="label">Name this address</label>
                <select class="field" id="label" name="label">
                    @foreach (['Home', 'Work', 'Family', 'Other'] as $label)
                        <option @selected(old('label') === $label)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="address">Full address</label>
                <textarea class="field" id="address" name="address" rows="2" required maxlength="500" autocomplete="street-address" placeholder="House no., street, mohalla">{{ old('address') }}</textarea>
                @error('address', 'address')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="field-label" for="landmark">Landmark</label>
                <input class="field" id="landmark" name="landmark" value="{{ old('landmark') }}" maxlength="150" placeholder="Near…">
            </div>
            <button class="btn btn-line w-full sm:w-auto">Save address</button>
        </form>
    </div>
</div>
@endsection
