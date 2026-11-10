<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use App\Models\Reservation;
use Filament\Widgets\Widget;

class TopDishes extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected string $view = 'filament.widgets.top-dishes';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view orders');
    }

    protected function getViewData(): array
    {
        $dishes = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled')->where('created_at', '>=', now()->subDays(30)))
            ->selectRaw('name, SUM(quantity) as qty, SUM(total) as revenue')
            ->groupBy('name')
            ->orderByDesc('qty')
            ->limit(6)
            ->get();

        return [
            'dishes' => $dishes,
            'max' => max(1, (int) $dishes->max('qty')),
            'bookings' => Reservation::whereDate('date', today())->whereIn('status', ['pending', 'confirmed'])->orderBy('time')->limit(5)->get(),
        ];
    }
}
