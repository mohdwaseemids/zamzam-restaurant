<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\LoyaltyTransaction;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * One place that prices and creates orders, so the website cart, the staff counter
 * and the mobile app API always agree on totals, stock, coupons and loyalty points.
 */
class OrderService
{
    public function __construct(protected StoreHours $hours) {}

    /**
     * Price a basket. Nothing is saved.
     *
     * @param  array<int, int>  $quantities  menu_item_id => quantity
     * @return array<string, mixed>
     */
    public function quote(array $quantities, string $type = 'delivery', ?User $user = null, ?string $couponCode = null, ?int $areaId = null, bool $usePoints = false): array
    {
        $dishes = MenuItem::available()
            ->whereIn('id', array_keys($quantities))
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->get()->keyBy('id');

        $items = [];
        $notices = [];
        $subtotal = 0.0;
        $count = 0;

        foreach ($quantities as $id => $quantity) {
            $dish = $dishes->get($id);
            $quantity = (int) $quantity;

            if (! $dish || $quantity < 1) {
                continue;
            }

            if ($dish->stock_qty !== null && $quantity > $dish->stock_qty) {
                if ($dish->stock_qty < 1) {
                    continue;
                }

                $notices[] = 'Only '.$dish->stock_qty.' of '.$dish->name.' left today.';
                $quantity = $dish->stock_qty;
            }

            $line = round($dish->final_price * $quantity, 2);
            $subtotal += $line;
            $count += $quantity;

            $items[] = [
                'id' => $dish->id,
                'name' => $dish->name,
                'slug' => $dish->slug,
                'portion' => $dish->portion,
                'image' => $dish->image_url,
                'price' => $dish->final_price,
                'quantity' => $quantity,
                'total' => $line,
                'is_veg' => $dish->is_veg,
            ];
        }

        $type = $this->normaliseType($type);

        // ---- Delivery area and charge
        $areas = $type === 'delivery' ? DeliveryArea::active()->get() : collect();
        $area = $areas->firstWhere('id', $areaId);
        $delivery = 0.0;
        $minOrder = (float) setting('min_order', 0);
        $freeAbove = (float) setting('free_delivery_above', 0);

        if ($type === 'delivery' && $subtotal > 0) {
            $charge = $area ? (float) $area->charge : (float) setting('delivery_charge', 0);
            $delivery = ($freeAbove > 0 && $subtotal >= $freeAbove) ? 0.0 : $charge;
            $minOrder = max($minOrder, (float) ($area?->min_order ?? 0));
        }

        if ($type !== 'delivery') {
            $minOrder = $type === 'dine_in' ? 0.0 : $minOrder;
        }

        // ---- Coupon (re-checked on every quote)
        $discount = 0.0;
        $coupon = $subtotal > 0 ? Coupon::findByCode($couponCode) : null;
        $couponError = $coupon?->problemFor($subtotal);

        if ($coupon && ! $couponError) {
            $discount = $coupon->discountFor($subtotal);
        }

        // ---- Loyalty points
        $points = $this->pointsFor($user, $subtotal - $discount, $usePoints);

        $taxable = max(0, $subtotal - $discount - $points['discount']);
        $tax = round($taxable * ((float) setting('tax_percent', 0)) / 100, 2);
        $store = $this->hours->status();

        return [
            'items' => $items,
            'count' => $count,
            'notices' => $notices,
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'coupon_code' => $coupon?->code,
            'coupon_error' => $couponError,
            'delivery_charge' => $delivery,
            'tax' => $tax,
            'total' => round($taxable + $delivery + $tax, 2),
            'type' => $type,
            'min_order' => $minOrder,
            'meets_minimum' => $subtotal >= $minOrder,
            'free_delivery_above' => $freeAbove,
            'area_required' => $type === 'delivery' && $areas->isNotEmpty(),
            'area_id' => $area?->id,
            'area_name' => $area?->name,
            'points_balance' => $points['balance'],
            'points_redeemable' => $points['redeemable'],
            'points_used' => $points['used'],
            'points_discount' => $points['discount'],
            'points_will_earn' => $this->pointsEarnedOn($taxable, $user),
            'store_open' => $store['open'],
            'store_message' => $store['message'],
        ];
    }

    /**
     * Save a quoted basket as an order. Stock, coupon use and points are settled inside one transaction.
     *
     * @param  array<string, mixed>  $quote  result of quote()
     * @param  array{customer_name: string, phone: string, email?: ?string, address?: ?string, landmark?: ?string, notes?: ?string}  $customer
     *
     * @throws ValidationException
     */
    public function place(array $quote, array $customer, string $paymentMethod, ?User $user = null, string $source = 'web', ?RestaurantTable $table = null): Order
    {
        if ($quote['count'] === 0) {
            throw ValidationException::withMessages(['cart' => 'Your order is empty.']);
        }

        if (! $quote['store_open']) {
            throw ValidationException::withMessages(['cart' => $quote['store_message'] ?? 'Online ordering is closed right now.']);
        }

        if (! $quote['meets_minimum']) {
            throw ValidationException::withMessages(['cart' => 'Minimum order is '.money($quote['min_order']).'. Please add a little more.']);
        }

        if ($quote['area_required'] && ! $quote['area_id']) {
            throw ValidationException::withMessages(['area_id' => 'Please choose your delivery area.']);
        }

        return DB::transaction(function () use ($quote, $customer, $paymentMethod, $user, $source, $table): Order {
            $this->takeStock($quote['items']);

            $isDelivery = $quote['type'] === 'delivery';

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'token' => Str::random(48),
                'user_id' => $user?->id,
                'customer_name' => $customer['customer_name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'] ?? null,
                'type' => $quote['type'],
                'address' => $isDelivery ? ($customer['address'] ?? null) : null,
                'landmark' => $isDelivery ? ($customer['landmark'] ?? null) : null,
                'delivery_area_id' => $isDelivery ? $quote['area_id'] : null,
                'restaurant_table_id' => $quote['type'] === 'dine_in' ? $table?->id : null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => $quote['subtotal'],
                'discount' => $quote['discount'],
                'coupon_code' => $quote['discount'] > 0 ? $quote['coupon_code'] : null,
                'points_redeemed' => $quote['points_used'],
                'points_discount' => $quote['points_discount'],
                'delivery_charge' => $quote['delivery_charge'],
                'tax' => $quote['tax'],
                'total' => $quote['total'],
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'status' => 'pending',
                'source' => $source,
            ]);

            foreach ($quote['items'] as $item) {
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

            if ($user && $order->points_redeemed > 0) {
                $this->spendPoints($user, $order);
            }

            return $order;
        });
    }

    /**
     * Record a confirmed online payment. Returns false when the order was already paid
     * (the browser callback, the app and the Razorpay webhook can all report the same payment).
     */
    public function markPaid(Order $order, ?string $paymentId, ?string $signature = null): bool
    {
        return DB::transaction(function () use ($order, $paymentId, $signature): bool {
            $fresh = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($fresh->payment_status === 'paid') {
                return false;
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
    }

    /**
     * Reduce stock for dishes that track it. Fails the whole order if someone else just took the last ones.
     *
     * @param  list<array{id: int, name: string, quantity: int}>  $items
     *
     * @throws ValidationException
     */
    public function takeStock(array $items): void
    {
        foreach ($items as $item) {
            $dish = MenuItem::whereKey($item['id'])->lockForUpdate()->first();

            if (! $dish || $dish->stock_qty === null) {
                continue;
            }

            if ($dish->stock_qty < $item['quantity']) {
                throw ValidationException::withMessages(['cart' => 'Sorry, only '.$dish->stock_qty.' of '.$dish->name.' left. Please update your order.']);
            }

            $dish->update(['stock_qty' => $dish->stock_qty - $item['quantity']]);
        }
    }

    /** Put stock back when an order is cancelled. */
    public function returnStock(Order $order): void
    {
        foreach ($order->items()->with('menuItem')->get() as $line) {
            $dish = $line->menuItem;

            if ($dish && $dish->stock_qty !== null) {
                $dish->update(['stock_qty' => $dish->stock_qty + $line->quantity]);
            }
        }
    }

    /**
     * @return array{balance: int, redeemable: int, used: int, discount: float}
     */
    protected function pointsFor(?User $user, float $payable, bool $usePoints): array
    {
        $none = ['balance' => (int) ($user?->loyalty_points ?? 0), 'redeemable' => 0, 'used' => 0, 'discount' => 0.0];

        if (! $user || ! setting('loyalty_enabled', true) || $payable <= 0) {
            return $none;
        }

        $value = max(0.01, (float) setting('loyalty_point_value', 1));
        $balance = (int) $user->loyalty_points;

        if ($balance < (int) setting('loyalty_min_redeem', 50)) {
            return $none;
        }

        $cap = (int) floor($payable * ((float) setting('loyalty_max_percent', 50)) / 100 / $value);
        $redeemable = max(0, min($balance, $cap));
        $used = $usePoints ? $redeemable : 0;

        return ['balance' => $balance, 'redeemable' => $redeemable, 'used' => $used, 'discount' => round($used * $value, 2)];
    }

    public function pointsEarnedOn(float $amount, ?User $user): int
    {
        if (! $user || ! setting('loyalty_enabled', true)) {
            return 0;
        }

        return (int) floor($amount / 100 * (float) setting('loyalty_earn_per_100', 5));
    }

    protected function spendPoints(User $user, Order $order): void
    {
        $fresh = User::whereKey($user->id)->lockForUpdate()->first();

        if ($fresh->loyalty_points < $order->points_redeemed) {
            throw ValidationException::withMessages(['cart' => 'Your points balance has changed. Please review your order.']);
        }

        $fresh->decrement('loyalty_points', $order->points_redeemed);

        LoyaltyTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'points' => -$order->points_redeemed,
            'description' => 'Used on order '.$order->order_number,
        ]);
    }

    /** Called when an order is completed. Safe to call twice. */
    public function awardPoints(Order $order): void
    {
        if (! $order->user_id || $order->points_earned > 0 || $order->source === 'counter') {
            return;
        }

        $user = $order->user;
        $points = $this->pointsEarnedOn((float) $order->subtotal - (float) $order->discount - (float) $order->points_discount, $user);

        if ($points < 1) {
            return;
        }

        DB::transaction(function () use ($order, $user, $points): void {
            $user->increment('loyalty_points', $points);
            $order->updateQuietly(['points_earned' => $points]);

            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'points' => $points,
                'description' => 'Earned on order '.$order->order_number,
            ]);
        });
    }

    /** Called when an order is cancelled: give back spent points, take back earned ones, restock. */
    public function reverse(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $this->returnStock($order);

            $user = $order->user;

            if (! $user) {
                return;
            }

            $net = (int) $order->points_redeemed - (int) $order->points_earned;

            if ($net === 0) {
                return;
            }

            $net > 0
                ? $user->increment('loyalty_points', $net)
                : $user->forceFill(['loyalty_points' => max(0, $user->loyalty_points + $net)])->save();

            LoyaltyTransaction::create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'points' => $net,
                'description' => 'Order '.$order->order_number.' cancelled',
            ]);

            $order->updateQuietly(['points_redeemed' => 0, 'points_earned' => 0]);
        });
    }

    protected function normaliseType(string $type): string
    {
        if ($type === 'dine_in') {
            return 'dine_in';
        }

        return ($type === 'delivery' && setting('delivery_enabled', true)) ? 'delivery' : 'pickup';
    }
}
