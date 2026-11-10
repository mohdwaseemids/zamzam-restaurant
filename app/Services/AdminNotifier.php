<?php

namespace App\Services;

use App\Filament\Resources\ContactMessageResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\ReviewResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Central place for staff alerts. Today it writes to the admin panel bell (database channel).
 *
 * FUTURE SCOPE: add push (FCM via the device_tokens table), WhatsApp or SMS here —
 * every alert in the system already passes through send().
 */
class AdminNotifier
{
    public static function newOrder(Order $order): void
    {
        static::send(
            'view orders',
            'New order '.$order->order_number,
            $order->customer_name.' · '.money($order->total).' · '.$order->type_label.' · '.($order->payment_status === 'paid' ? 'Paid online' : 'Cash'),
            'heroicon-o-shopping-bag',
            OrderResource::class,
        );
    }

    public static function newReservation(Reservation $reservation): void
    {
        static::send(
            'view reservations',
            'New table booking',
            $reservation->name.' · '.$reservation->guests.' guests · '.$reservation->date->format('d M').' '.$reservation->time,
            'heroicon-o-calendar-days',
            ReservationResource::class,
        );
    }

    public static function newReview(Review $review): void
    {
        static::send(
            'view reviews',
            'New review waiting for approval',
            $review->customer_name.' rated '.$review->rating.'★',
            'heroicon-o-star',
            ReviewResource::class,
        );
    }

    public static function newMessage(ContactMessage $message): void
    {
        static::send(
            'view messages',
            'New message from '.$message->name,
            str($message->message)->limit(80)->toString(),
            'heroicon-o-envelope',
            ContactMessageResource::class,
        );
    }

    /** @param class-string $resource */
    protected static function send(string $permission, string $title, string $body, string $icon, string $resource): void
    {
        try {
            $recipients = User::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->role('super_admin')->orWhere(fn ($q) => $q->permission($permission)))
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon($icon)
                ->actions([Action::make('open')->label('Open')->button()->url($resource::getUrl(panel: 'admin'))->markAsRead()])
                ->sendToDatabase($recipients);
        } catch (\Throwable $e) {
            // A notification problem must never break a customer's order.
            Log::warning('Admin notification failed: '.$e->getMessage());
        }
    }
}
