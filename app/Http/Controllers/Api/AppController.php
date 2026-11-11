<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\DeliveryArea;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use App\Services\AdminNotifier;
use App\Services\OrderService;
use App\Services\RazorpayService;
use App\Services\StoreHours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/** Ordering endpoints for the mobile app. Pricing rules are shared with the website through OrderService. */
class AppController extends Controller
{
    public function __construct(protected OrderService $orders, protected RazorpayService $razorpay, protected StoreHours $hours) {}

    /** Everything the app needs to know before showing checkout. */
    public function store(): JsonResponse
    {
        return response()->json([
            'status' => $this->hours->status(),
            'hours' => $this->hours->week(),
            'delivery_enabled' => (bool) setting('delivery_enabled', true),
            'delivery_areas' => DeliveryArea::active()->get(['id', 'name', 'pincode', 'charge', 'min_order']),
            'payment_methods' => $this->paymentMethods(),
            'min_order' => (float) setting('min_order', 0),
            'free_delivery_above' => (float) setting('free_delivery_above', 0),
            'loyalty' => [
                'enabled' => (bool) setting('loyalty_enabled', true),
                'earn_per_100' => (float) setting('loyalty_earn_per_100', 5),
                'point_value' => (float) setting('loyalty_point_value', 1),
                'min_redeem' => (int) setting('loyalty_min_redeem', 50),
            ],
        ]);
    }

    /** Price a basket without saving anything. Works for guests; send the token to include loyalty points. */
    public function quote(Request $request): JsonResponse
    {
        $data = $this->validateBasket($request);

        return response()->json($this->buildQuote($request, $data));
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->merge(['phone' => User::normalizePhone($request->input('phone', $user->phone))]);

        $data = $this->validateBasket($request) + $request->validate([
            'customer_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'address' => ['required_if:type,delivery', 'nullable', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($this->paymentMethods())],
        ], ['phone.regex' => 'Please enter a valid 10 digit mobile number.']);

        $order = $this->orders->place(
            $this->buildQuote($request, $data),
            ['customer_name' => $data['customer_name'] ?? $user->name, 'email' => $user->email] + $data,
            $data['payment_method'],
            $user,
            'app',
        );

        $payment = null;

        if ($order->payment_method === 'razorpay') {
            try {
                $payment = [
                    'key' => $this->razorpay->keyId(),
                    'razorpay_order_id' => $this->razorpay->createOrder($order),
                    'amount' => (int) round(((float) $order->total) * 100),
                    'currency' => 'INR',
                ];
            } catch (\Throwable $e) {
                Log::error('Razorpay order creation failed (app): '.$e->getMessage(), ['order' => $order->order_number]);

                return response()->json(['message' => 'We could not start the online payment. Please try again.', 'order' => $this->present($order)], 502);
            }
        } else {
            AdminNotifier::newOrder($order);
        }

        return response()->json(['order' => $this->present($order), 'payment' => $payment], 201);
    }

    /** Confirm a Razorpay payment made inside the app. */
    public function verifyPayment(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->ownOrder($request, $orderNumber);

        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $valid = filled($order->razorpay_order_id)
            && hash_equals($order->razorpay_order_id, $data['razorpay_order_id'])
            && $this->razorpay->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature']);

        if (! $valid) {
            return response()->json(['message' => 'We could not verify this payment.'], 422);
        }

        if ($this->orders->markPaid($order, $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            AdminNotifier::newOrder($order->fresh());
        }

        return response()->json(['order' => $this->present($order->fresh())]);
    }

    public function showOrder(Request $request, string $orderNumber): JsonResponse
    {
        return response()->json(['order' => $this->present($this->ownOrder($request, $orderNumber))]);
    }

    public function addresses(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses);
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'label' => ['required', 'string', 'max:40'],
            'address' => ['required', 'string', 'max:500'],
            'landmark' => ['nullable', 'string', 'max:150'],
        ]);

        abort_if($user->addresses()->count() >= 10, 422, 'You can save up to 10 addresses.');

        return response()->json($user->addresses()->create($data + ['is_default' => ! $user->addresses()->exists()]), 201);
    }

    public function destroyAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 404);

        $address->delete();

        return response()->json(['ok' => true]);
    }

    public function favourites(Request $request): JsonResponse
    {
        return response()->json($request->user()->favourites()->with('category:id,name,slug')->get());
    }

    public function toggleFavourite(Request $request, MenuItem $menuItem): JsonResponse
    {
        $result = $request->user()->favourites()->toggle($menuItem->id);

        return response()->json(['saved' => $result['attached'] !== []]);
    }

    public function loyalty(Request $request): JsonResponse
    {
        return response()->json([
            'points' => (int) $request->user()->loyalty_points,
            'history' => $request->user()->loyaltyTransactions()->take(30)->get(['points', 'description', 'created_at']),
        ]);
    }

    /**
     * @return array{items: list<array{id: int, quantity: int}>, type: string, area_id?: ?int, coupon?: ?string, use_points?: ?bool}
     */
    protected function validateBasket(Request $request): array
    {
        return $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:60'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'between:1,50'],
            'type' => ['required', Rule::in(setting('delivery_enabled', true) ? ['delivery', 'pickup'] : ['pickup'])],
            'area_id' => ['nullable', 'integer'],
            'coupon' => ['nullable', 'string', 'max:40'],
            'use_points' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildQuote(Request $request, array $data): array
    {
        $quantities = collect($data['items'])->mapWithKeys(fn (array $line): array => [(int) $line['id'] => (int) $line['quantity']])->all();

        return $this->orders->quote(
            $quantities,
            $data['type'],
            $request->user('sanctum'),
            $data['coupon'] ?? null,
            $data['area_id'] ?? null,
            (bool) ($data['use_points'] ?? false),
        );
    }

    /**
     * @return list<string>
     */
    protected function paymentMethods(): array
    {
        return array_keys(array_filter([
            'razorpay' => setting('online_payment_enabled', true) && $this->razorpay->isConfigured(),
            'cod' => (bool) setting('cod_enabled', true),
        ]));
    }

    protected function ownOrder(Request $request, string $orderNumber): Order
    {
        return $request->user()->orders()->where('order_number', $orderNumber)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(Order $order): array
    {
        $order->loadMissing('items');

        return $order->only([
            'order_number', 'status', 'type', 'customer_name', 'phone', 'address', 'landmark', 'notes', 'subtotal', 'discount',
            'coupon_code', 'points_redeemed', 'points_discount', 'points_earned', 'delivery_charge', 'tax', 'total',
            'payment_method', 'payment_status', 'created_at',
        ]) + [
            'status_label' => $order->status_label,
            'next_status' => $order->nextStatus(),
            'track_url' => route('order.show', $order->token),
            'items' => $order->items->map->only(['name', 'price', 'quantity', 'total'])->all(),
        ];
    }
}
