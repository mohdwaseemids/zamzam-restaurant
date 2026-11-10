@extends('site.auth.layout')

@section('title', 'Create account — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('eyebrow', 'Join the family')
@section('heading', 'Create your account')
@section('lead', 'Save your address once, see every order and reorder in one tap.')

@section('form')
<form method="POST" action="{{ route('register.store') }}" class="space-y-6" x-data="{ show: false }">
    @csrf
    <div>
        <label class="field-label" for="name">Full name *</label>
        <input class="field" id="name" name="name" value="{{ old('name') }}" required maxlength="100" autofocus autocomplete="name">
        @error('name')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="phone">Mobile number *</label>
        <input class="field" id="phone" name="phone" type="tel" inputmode="numeric" value="{{ old('phone') }}" required maxlength="14" autocomplete="tel-national" placeholder="10 digit mobile">
        @error('phone')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="email">Email <span class="normal-case tracking-normal text-sand/70">(optional — needed to reset a forgotten password)</span></label>
        <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="150" autocomplete="email">
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="password">Password * <span class="normal-case tracking-normal text-sand/70">(at least 8 characters)</span></label>
        <div class="relative">
            <input class="field pr-16" id="password" name="password" :type="show ? 'text' : 'password'" required minlength="8" autocomplete="new-password">
            <button type="button" class="absolute inset-y-0 right-0 px-4 text-xs uppercase tracking-[0.18em] text-sand hover:text-gold" @click="show = !show" x-text="show ? 'Hide' : 'Show'">Show</button>
        </div>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="password_confirmation">Confirm password *</label>
        <input class="field" id="password_confirmation" name="password_confirmation" :type="show ? 'text' : 'password'" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn btn-gold w-full">Create account</button>
</form>
@endsection

@section('below')
Already have an account? <a href="{{ route('login') }}" class="text-gold hover:text-gold-light">Sign in</a>
@endsection
