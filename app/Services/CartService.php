<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\RestaurantTable;

/**
 * The website cart, kept in the session: dishes, coupon, delivery area, "use my points"
 * and the table the guest scanned. Pricing itself is done by OrderService.
 */
class CartService
{
    protected const KEY = 'cart';

    protected const COUPON_KEY = 'cart_coupon';

    protected const AREA_KEY = 'cart_area';

    protected const POINTS_KEY = 'cart_points';

    public const TABLE_KEY = 'dine_table';

    public const MAX_QTY = 50;

    public function __construct(protected OrderService $orders) {}

    /**
     * @return array<int, int>
     */
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

    /** Empty the basket after an order. The scanned table stays so guests can order again. */
    public function clear(): void
    {
        session()->forget([self::KEY, self::COUPON_KEY, self::POINTS_KEY]);
    }

    public function setCoupon(?string $code): void
    {
        blank($code) ? session()->forget(self::COUPON_KEY) : session([self::COUPON_KEY => strtoupper(trim($code))]);
    }

    public function setArea(?int $areaId): void
    {
        $areaId ? session([self::AREA_KEY => $areaId]) : session()->forget(self::AREA_KEY);
    }

    public function usePoints(bool $use): void
    {
        $use ? session([self::POINTS_KEY => true]) : session()->forget(self::POINTS_KEY);
    }

    public function table(): ?RestaurantTable
    {
        $id = session(self::TABLE_KEY);

        return $id ? RestaurantTable::where('is_active', true)->find($id) : null;
    }

    public function isEmpty(): bool
    {
        return $this->summary()['count'] === 0;
    }

    /**
     * Full cart with live prices, charges and totals.
     *
     * @return array<string, mixed>
     */
    public function summary(string $type = 'delivery'): array
    {
        $table = $this->table();
        $code = session(self::COUPON_KEY);

        if ($code && ! Coupon::findByCode($code)) {
            session()->forget(self::COUPON_KEY);
            $code = null;
        }

        $quote = $this->orders->quote(
            $this->raw(),
            $table ? 'dine_in' : ($type === 'dine_in' ? 'pickup' : $type),
            auth()->user(),
            $code,
            session(self::AREA_KEY),
            (bool) session(self::POINTS_KEY),
        );

        // Keep the session in step with what is actually orderable (sold out, stock-capped).
        $orderable = collect($quote['items'])->pluck('quantity', 'id')->all();

        if ($orderable != $this->raw()) {
            session([self::KEY => $orderable]);
        }

        return $quote + [
            'table_name' => $table?->name,
        ];
    }
}
