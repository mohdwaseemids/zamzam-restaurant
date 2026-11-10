<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Coupon $coupon) => $coupon->code = strtoupper(trim($coupon->code)));
    }

    public static function findByCode(?string $code): ?self
    {
        return filled($code) ? static::where('code', strtoupper(trim($code)))->first() : null;
    }

    /** Reason this coupon cannot be used on the given subtotal, or null when it is valid. */
    public function problemFor(float $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'This coupon is not active.',
            $this->starts_at && $this->starts_at->isFuture() => 'This coupon is not valid yet.',
            $this->expires_at && $this->expires_at->isPast() => 'This coupon has expired.',
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => 'This coupon has been fully used.',
            $subtotal < (float) $this->min_order => 'Add '.money((float) $this->min_order - $subtotal).' more to use this coupon.',
            default => null,
        };
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->type === 'percent'
            ? $subtotal * min(100, (float) $this->value) / 100
            : (float) $this->value;

        if ($this->max_discount !== null && (float) $this->max_discount > 0) {
            $discount = min($discount, (float) $this->max_discount);
        }

        return round(min($discount, $subtotal), 2);
    }

    public function getLabelAttribute(): string
    {
        return $this->type === 'percent' ? rtrim(rtrim((string) $this->value, '0'), '.').'% off' : money($this->value).' off';
    }
}
