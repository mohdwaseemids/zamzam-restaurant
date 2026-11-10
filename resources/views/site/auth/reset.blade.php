@extends('site.auth.layout')

@section('title', 'Set a new password — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('eyebrow', 'Account help')
@section('heading', 'Set a new password')

@section('form')
<form method="POST" action="{{ route('password.update') }}" class="space-y-6">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div>
        <label class="field-label" for="email">Email</label>
        <input class="field" id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email">
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="password">New password <span class="normal-case tracking-normal text-sand/70">(at least 8 characters)</span></label>
        <input class="field" id="password" name="password" type="password" required minlength="8" autofocus autocomplete="new-password">
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="field-label" for="password_confirmation">Confirm new password</label>
        <input class="field" id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
    </div>
    <button class="btn btn-gold w-full">Change password</button>
</form>
@endsection

@section('below')
<a href="{{ route('login') }}" class="text-gold hover:text-gold-light">← Back to sign in</a>
@endsection
