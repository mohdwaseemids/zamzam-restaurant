@extends('site.auth.layout')

@section('title', 'Forgot password — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('eyebrow', 'Account help')
@section('heading', 'Forgot your password?')
@section('lead', 'Enter the email saved on your account and we will send you a link to set a new password.')

@section('form')
<form method="POST" action="{{ route('password.email') }}" class="space-y-6">
    @csrf
    <div>
        <label class="field-label" for="email">Email</label>
        <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        @error('email')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <button class="btn btn-gold w-full">Send reset link</button>
</form>

<p class="mt-6 border-t border-white/5 pt-5 text-sm text-sand">
    No email on your account? @if (setting('phone'))Call us on <a href="tel:{{ setting('phone') }}" class="text-gold">{{ setting('phone') }}</a>@else Contact the restaurant @endif and we will reset it for you after confirming your mobile number.
</p>
@endsection

@section('below')
<a href="{{ route('login') }}" class="text-gold hover:text-gold-light">← Back to sign in</a>
@endsection
