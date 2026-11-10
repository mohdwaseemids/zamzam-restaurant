<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Services\AdminNotifier;
use App\Services\CartService;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(protected CartService $cart, protected RazorpayService $razorpay) {}

    public function show()
    {
        if (! setting('ordering_enabled', true)) {
            return redirect()->route('menu')->with('error', 'Online ordering is closed right now. Please call us to order.');
        }

        if ($this->cart->isEmpty()) {
            return redirect()->route('menu')->with('error', 'Your order is empty. Add a few dishes first.');
        }

        return view('site.checkout', [
            'summary' => $this->cart->summary(old('type', 'delivery')),
            'onlineAvailable' => $this->onlineAvailable(),
            'codAvailable' => (bool) setting('cod_enabled', true),
            'deliveryAvailable' => (bool) setting('delivery_enabled', true),
            'customer' => auth()->user(),
            'addresses' => auth()->user()?->addresses ?? collect(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless((bool) setting('ordering_enabled', true), 403, 'Online ordering is closed.');

        $methods = array_keys(array_filter([
            'razorpay' => $this->onlineAvailable(),
            'cod' => (bool) setting('cod_enabled', true),
        ]));

        $types = setting('delivery_enabled', true) ? ['delivery', 'pickup'] : ['pickup'];

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[6-9][0-9]{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'type' => ['required', Rule::in($types)],
            'address' => ['required_if:type,delivery', 'nullable', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($methods)],
            'save_address' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'Please enter a valid 10 digit mobile number.',
            'address.required_if' => 'Please enter your delivery address.',
            'payment_method.in' => 'This payment method is not available right now.',
        ]);

        $summary = $this->cart->summary($data['type']);

        if ($summary['count'] === 0) {
            return redirect()->route('menu')->with('error', 'Your order is empty.');
        }

        if (! $summary['meets_minimum']) {
            return back()->withInput()->with('error', 'Minimum order is '.money($summary['min_order']).'. Please add a little more.');
        }

        $order = DB::transaction(function () use ($data, $summary) {
            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'token' => Str::random(48),
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'type' => $summary['type'],
                'address' => $summary['type'] === 'delivery' ? $data['address'] : null,
                'landmark' => $summary['type'] === 'delivery' ? ($data['landmark'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'coupon_code' => $summary['discount'] > 0 ? $summary['coupon_code'] : null,
                'delivery_charge' => $summary['delivery_charge'],
                'tax' => $summary['tax'],
                'total' => $summary['total'],
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
                'status' => 'pending',
                'source' => 'web',
            ]);

            foreach ($summary['items'] as $item) {
                $order->items()->create([
                    'menu_item_id' => $item['id'],
                    'name' => $item['name'].($item['portion'] ? ' ('.$item['portion'].')' : ''),
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'total' => $item['total'],
                ]);
            }

            if ($order->coupon_code) {
                Coupon::where('code', $order->coupon_code)->increment('used_count');
            }

            return $order;
        });

        // Signed-in customers can keep a new delivery address for next time.
        if (auth()->check() && $order->type === 'delivery' && $request->boolean('save_address')) {
            $user = auth()->user();

            if (! $user->addresses()->where('address', $order->address)->exists() && $user->addresses()->count() < 10) {
                $user->addresses()->create([
                    'label' => 'Home',
                    'address' => $order->address,
                    'landmark' => $order->landmark,
                    'is_default' => ! $user->addresses()->exists(),
                ]);
            }
        }

        if ($order->payment_method === 'cod') {
            $this->cart->clear();
            AdminNotifier::newOrder($order);

            return redirect()->route('order.show', $order->token)->with('placed', true);
        }

        return redirect()->route('order.pay', $order->token);
    }

    /** Razorpay payment page (also used to retry an unpaid online order). */
    public function pay(string $token)
    {
        $order = Order::where('token', $token)->firstOrFail();

        if ($order->payment_status === 'paid' || $order->payment_method !== 'razorpay' || $order->status === 'cancelled') {
            return redirect()->route('order.show', $order->token);
        }

        if (! $this->razorpay->isConfigured()) {
            return redirect()->route('order.show', $order->token)->with('error', 'Online payment is not available right now.');
        }

        try {
            $razorpayOrderId = $this->razorpay->createOrder($order);
        } catch (\Throwable $e) {
            Log::error('Razorpay order creation failed: '.$e->getMessage(), ['order' => $order->order_number]);

            return redirect()->route('order.show', $order->token)->with('error', 'We could not start the online payment. Please try again or call us.');
        }

        return view('site.pay', [
            'order' => $order,
            'razorpayKey' => $this->razorpay->keyId(),
            'razorpayOrderId' => $razorpayOrderId,
        ]);
    }

    public function verify(Request $request, string $token)
    {
        $order = Order::where('token', $token)->firstOrFail();

        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $valid = filled($order->razorpay_order_id)
            && hash_equals($order->razorpay_order_id, $data['razorpay_order_id'])
            && $this->razorpay->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);

        if (! $valid) {
            Log::warning('Razorpay signature mismatch', ['order' => $order->order_number]);

            return redirect()->route('order.show', $order->token)->with('error', 'We could not verify this payment. If money was deducted, it will be confirmed or refunded automatically.');
        }

        $this->markPaid($order, $data['razorpay_payment_id'], $data['razorpay_signature']);
        $this->cart->clear();

        return redirect()->route('order.show', $order->token)->with('placed', true);
    }

    /** Server-to-server confirmation from Razorpay (covers customers who close the browser after paying). */
    public function webhook(Request $request)
    {
        if (! $this->razorpay->verifyWebhookSignature($request->getContent(), $request->header('X-Razorpay-Signature'))) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $payment = $request->input('payload.payment.entity', []);
        $order = Order::where('razorpay_order_id', $payment['order_id'] ?? '')->first();

        if ($order) {
            if ($event === 'payment.captured') {
                $this->markPaid($order, $payment['id'] ?? null);
            } elseif ($event === 'payment.failed' && $order->payment_status === 'pending') {
                $order->update(['payment_status' => 'failed']);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function order(string $token)
    {
        $order = Order::where('token', $token)->with('items')->firstOrFail();

        return view('site.order', compact('order'));
    }

    public function track()
    {
        return view('site.track');
    }

    public function findOrder(Request $request)
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $order = Order::where('order_number', strtoupper(trim($data['order_number'])))
            ->where('phone', preg_replace('/\D/', '', $data['phone']))
            ->first();

        if (! $order) {
            return back()->withInput()->with('error', 'No order found with this order number and mobile number.');
        }

        return redirect()->route('order.show', $order->token);
    }

    protected function onlineAvailable(): bool
    {
        return (bool) setting('online_payment_enabled', true) && $this->razorpay->isConfigured();
    }

    protected function markPaid(Order $order, ?string $paymentId, ?string $signature = null): void
    {
        $justPaid = DB::transaction(function () use ($order, $paymentId, $signature) {
            $fresh = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($fresh->payment_status === 'paid') {
                return false; // browser callback and webhook can both arrive
            }

            $fresh->update([
                'payment_status' => 'paid',
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature ?? $fresh->razorpay_signature,
                'paid_at' => now(),
                'status' => $fresh->status === 'pending' ? 'confirmed' : $fresh->status,
            ]);

            return true;
        });

        if ($justPaid) {
            AdminNotifier::newOrder($order->fresh());
        }
    }
}
