<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/** Customer accounts: sign up with a mobile number, sign in with mobile or email. */
class AuthController extends Controller
{
    /** "Sign in" links on the checkout page bring the customer straight back to it. */
    protected function rememberCheckout(): void
    {
        if (request()->query('next') === 'checkout') {
            session(['url.intended' => route('checkout')]);
        }
    }

    public function showRegister()
    {
        $this->rememberCheckout();

        return view('site.auth.register');
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,phone'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'phone.regex' => 'Please enter a valid 10 digit mobile number.',
            'phone.unique' => 'An account with this mobile number already exists. Please sign in.',
            'email.unique' => 'An account with this email already exists. Please sign in.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('account'))->with('success', 'Welcome to Zam Zam, '.Str::before($user->name, ' ').'! Your account is ready.');
    }

    public function showLogin()
    {
        $this->rememberCheckout();

        return view('site.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $credentials = str_contains($data['login'], '@')
            ? ['email' => strtolower(trim($data['login']))]
            : ['phone' => User::normalizePhone($data['login'])];

        // is_active in the credentials means a blocked account can never sign in.
        if (! Auth::attempt($credentials + ['password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['login' => 'The mobile number / email or password is not correct.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been signed out.');
    }

    public function showForgot()
    {
        return view('site.auth.forgot');
    }

    public function sendReset(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink(['email' => strtolower(trim($request->input('email')))]);

        // Same answer whether or not the email exists, so nobody can probe for accounts.
        return back()->with('success', 'If an account uses this email, a password reset link has been sent to it.');
    }

    public function showReset(Request $request, string $token)
    {
        return view('site.auth.reset', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'This reset link is invalid or has expired. Please request a new one.']);
        }

        return redirect()->route('login')->with('success', 'Your password has been changed. Please sign in.');
    }
}
