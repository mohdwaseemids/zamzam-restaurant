<?php

namespace App\Models;

use App\Services\OrderService;
use App\Services\PushService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'points_discount' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for delivery',
        'ready' => 'Ready for pickup',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    public const PAYMENT_METHODS = [
        'razorpay' => 'Online (Razorpay)',
        'cod' => 'Cash',
        'counter_upi' => 'UPI / card at counter',
    ];

    public const TYPES = [
        'delivery' => 'Home delivery',
        'pickup' => 'Takeaway',
        'dine_in' => 'Dine in',
    ];

    public const STATUS_COLORS = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'preparing' => 'info',
        'out_for_delivery' => 'primary',
        'ready' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];

    protected static function booted(): void
    {
        // Side effects of a status change: loyalty points, stock and the customer's push notification.
        static::updated(function (Order $order): void {
            if (! $order->wasChanged('status')) {
                return;
            }

            $service = app(OrderService::class);

            if ($order->status === 'completed') {
                $service->awardPoints($order);
            }

            if ($order->status === 'cancelled') {
                $service->reverse($order);
            }

            app(PushService::class)->orderStatusChanged($order);
        });
    }

    public function deliveryArea(): BelongsTo
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    /** GST invoice number, e.g. ZZ/2026-27/0042. Issued once, in sequence, per financial year. */
    public function ensureInvoiceNumber(): string
    {
        if ($this->invoice_number) {
            return $this->invoice_number;
        }

        return DB::transaction(function (): string {
            $start = $this->created_at->month >= 4 ? $this->created_at->year : $this->created_at->year - 1;
            $prefix = 'ZZ/'.$start.'-'.substr((string) ($start + 1), -2).'/';

            $last = static::where('invoice_number', 'like', $prefix.'%')->lockForUpdate()->max('invoice_number');
            $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            $this->updateQuietly(['invoice_number' => $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT)]);

            return $this->invoice_number;
        });
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'ZZ'.now()->format('ymd').strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    /** The step that normally follows the current status, or null when the order is finished. */
    public function nextStatus(): ?string
    {
        $flow = $this->type === 'delivery'
            ? ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'completed']
            : ['pending', 'confirmed', 'preparing', 'ready', 'completed'];

        $index = array_search($this->status, $flow, true);

        return $index === false ? null : ($flow[$index + 1] ?? null);
    }

    /** Move to a status; cash orders are treated as paid once they are completed. */
    public function moveTo(string $status): void
    {
        $changes = ['status' => $status];

        if ($status === 'completed' && $this->payment_status === 'pending' && $this->payment_method !== 'razorpay') {
            $changes += ['payment_status' => 'paid', 'paid_at' => now()];
        }

        $this->update($changes);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }
}
