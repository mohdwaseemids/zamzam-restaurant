<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\MenuItem;

/** Session based cart: [menu_item_id => quantity]. Prices are always read from the database. */
class CartService
{
    protected const KEY = 'cart';

    protected const COUPON_KEY = 'cart_coupon';

    public const MAX_QTY = 50;

    public function raw(): array
    {
        return session(self::KEY, []);
    }

    public function add(int $itemId, int $quantity = 1): void
    {
        $cart = $this->raw();
        $cart[$itemId] = min(self::MAX_QTY, ($cart[$itemId] ?? 0) + max(1, $quantity));
        session([self::KEY => $cart]);
    }

    public function set(int $itemId, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$itemId]);
        } else {
            $cart[$itemId] = min(self::MAX_QTY, $quantity);
        }

        session([self::KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, self::COUPON_KEY]);
    }

    public function setCoupon(?string $code): void
    {
        blank($code) ? session()->forget(self::COUPON_KEY) : session([self::COUPON_KEY => strtoupper(trim($code))]);
    }

    public function isEmpty(): bool
    {
        return $this->summary()['count'] === 0;
    }

    /** Full cart with live prices, charges and totals. */
    public function summary(string $type = 'delivery'): array
    {
        $cart = $this->raw();
        $models = MenuItem::available()
            ->whereIn('id', array_keys($cart))
            ->whereHas('category', fn ($q) => $q->where('is_active', true))
            ->get()->keyBy('id');

        $items = [];
        $subtotal = 0.0;
        $count = 0;

        foreach ($cart as $id => $qty) {
            $model = $models->get($id);

            if (! $model) {
                continue; // removed or unavailable since it was added
            }

            $line = round($model->final_price * $qty, 2);
            $subtotal += $line;
            $count += $qty;

            $items[] = [
                'id' => $model->id,
                'name' => $model->name,
                'portion' => $model->portion,
                'image' => $model->image_url,
                'price' => $model->final_price,
                'quantity' => $qty,
                'total' => $line,
                'is_veg' => $model->is_veg,
            ];
        }

        // Drop stale ids so the session stays in sync with what is shown.
        if (count($items) !== count($cart)) {
            session([self::KEY => collect($items)->pluck('quantity', 'id')->all()]);
        }

        $deliveryEnabled = (bool) setting('delivery_enabled', true);
        $type = ($type === 'delivery' && $deliveryEnabled) ? 'delivery' : 'pickup';

        $delivery = 0.0;
        if ($type === 'delivery' && $subtotal > 0) {
            $free = (float) setting('free_delivery_above', 0);
            $delivery = ($free > 0 && $subtotal >= $free) ? 0.0 : (float) setting('delivery_charge', 0);
        }

        // Coupon: re-validated on every read so an expired or used-up code never applies.
        $discount = 0.0;
        $couponCode = null;
        $couponError = null;

        if ($subtotal > 0 && ($coupon = Coupon::findByCode(session(self::COUPON_KEY)))) {
            if ($couponError = $coupon->problemFor($subtotal)) {
                // keep the code in the session: it may become valid once more dishes are added
            } else {
                $discount = $coupon->discountFor($subtotal);
            }
            $couponCode = $coupon->code;
        } elseif (session()->has(self::COUPON_KEY)) {
            session()->forget(self::COUPON_KEY);
        }

        $tax = round(($subtotal - $discount) * ((float) setting('tax_percent', 0)) / 100, 2);
        $minOrder = (float) setting('min_order', 0);

        return [
            'items' => $items,
            'count' => $count,
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'coupon_code' => $couponCode,
            'coupon_error' => $couponError,
            'delivery_charge' => $delivery,
            'tax' => $tax,
            'total' => round($subtotal - $discount + $delivery + $tax, 2),
            'type' => $type,
            'min_order' => $minOrder,
            'meets_minimum' => $subtotal >= $minOrder,
            'free_delivery_above' => (float) setting('free_delivery_above', 0),
        ];
    }
}
