<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AccountController extends Controller
{
    /** Orders this customer placed themself (not counter bills typed in by staff). */
    protected function orders(Request $request)
    {
        return $request->user()->orders()->where('source', '!=', 'counter');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $orders = $this->orders($request);

        return view('site.account.index', [
            'user' => $user,
            'recentOrders' => (clone $orders)->withCount('items')->latest()->take(4)->get(),
            'activeOrder' => (clone $orders)->whereNotIn('status', ['completed', 'cancelled'])->latest()->first(),
            'stats' => [
                'orders' => (clone $orders)->where('status', '!=', 'cancelled')->count(),
                'spent' => (float) (clone $orders)->where('status', '!=', 'cancelled')->sum('total'),
                'addresses' => $user->addresses()->count(),
            ],
            'bookings' => $user->reservations()->whereDate('date', '>=', today())->orderBy('date')->orderBy('time')->take(3)->get(),
        ]);
    }

    public function orderHistory(Request $request)
    {
        return view('site.account.orders', [
            'orders' => $this->orders($request)->with('items')->latest()->paginate(8),
        ]);
    }

    /** Put every still-available dish of an old order back into the cart. */
    public function reorder(Request $request, Order $order, CartService $cart)
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $added = 0;

        foreach ($order->items()->with('menuItem')->get() as $line) {
            $dish = $line->menuItem;

            if ($dish && ! $dish->trashed() && $dish->is_available) {
                $cart->add($dish->id, $line->quantity);
                $added++;
            }
        }

        if ($added === 0) {
            return back()->with('error', 'Sorry, the dishes from this order are not available right now.');
        }

        $missing = $order->items()->count() - $added;

        return redirect()->route('checkout')->with('success', $missing > 0
            ? 'Added to your order. '.$missing.' '.str('dish')->plural($missing).' from last time '.($missing > 1 ? 'are' : 'is').' not available today.'
            : 'Your last order is back in the cart.');
    }

    public function settings(Request $request)
    {
        return view('site.account.settings', [
            'user' => $request->user(),
            'addresses' => $request->user()->addresses,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validateWithBag('profile', [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/', Rule::unique('users', 'phone')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'phone.regex' => 'Please enter a valid 10 digit mobile number.',
            'phone.unique' => 'This mobile number is used by another account.',
            'email.unique' => 'This email is used by another account.',
        ]);

        $user->update($data);

        return back()->with('success', 'Your details have been updated.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], ['current_password.current_password' => 'Your current password is not correct.']);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Your password has been changed.');
    }

    public function storeAddress(Request $request)
    {
        $user = $request->user();

        $data = $request->validateWithBag('address', [
            'label' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:150'],
        ]);

        if ($user->addresses()->count() >= 10) {
            return back()->with('error', 'You can save up to 10 addresses. Please remove one first.');
        }

        $user->addresses()->create($data + ['is_default' => ! $user->addresses()->exists()]);

        return back()->with('success', 'Address saved.');
    }

    public function makeDefaultAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', 'Default address updated.');
    }

    public function destroyAddress(Request $request, Address $address)
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $request->user()->addresses()->reorder()->latest('id')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', 'Address removed.');
    }
}
