@extends('site.auth.layout')

@section('title', 'Sign in — '.setting('site_name', 'Zam Zam Family Restaurant'))
@section('eyebrow', 'Welcome back')
@section('heading', 'Sign in')
@section('lead', 'Track your orders, reorder favourites and check out faster.')

@section('form')
<form method="POST" action="{{ route('login.attempt') }}" class="space-y-6" x-data="{ show: false }">
    @csrf
    <div>
        <label class="field-label" for="login">Mobile number or email</label>
        <input class="field" id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" inputmode="email" placeholder="98xxxxxxxx">
        @error('login')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <div class="flex items-baseline justify-between">
            <label class="field-label" for="password">Password</label>
            <a href="{{ route('password.request') }}" class="text-sm text-gold hover:text-gold-light">Forgot password?</a>
        </div>
        <div class="relative">
            <input class="field pr-16" id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password">
            <button type="button" class="absolute inset-y-0 right-0 px-4 text-xs uppercase tracking-[0.18em] text-sand hover:text-gold" @click="show = !show" x-text="show ? 'Hide' : 'Show'" :aria-label="show ? 'Hide password' : 'Show password'">Show</button>
        </div>
        @error('password')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <label class="flex cursor-pointer items-center gap-3 text-cream/90">
        <input type="checkbox" name="remember" value="1" class="size-4 accent-[#c9a45c]" checked> Keep me signed in on this device
    </label>
    <button class="btn btn-gold w-full">Sign in</button>
</form>
@endsection

@section('below')
New here? <a href="{{ route('register') }}" class="text-gold hover:text-gold-light">Create an account</a>
<span class="mx-2 text-white/20">|</span>
<a href="{{ route('menu') }}" class="hover:text-gold">Order as guest</a>
@endsection
