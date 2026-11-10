<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RestaurantStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view orders');
    }

    protected function getStats(): array
    {
        $valid = fn () => Order::where('status', '!=', 'cancelled');

        $todayRevenue = (float) $valid()->whereDate('created_at', today())->sum('total');
        $monthRevenue = (float) $valid()->where('created_at', '>=', now()->startOfMonth())->sum('total');
        $todayOrders = Order::whereDate('created_at', today())->count();
        $pending = Order::where('status', 'pending')->count();

        $trend = collect(range(6, 0))->map(
            fn ($d) => (float) $valid()->whereDate('created_at', today()->subDays($d))->sum('total')
        )->all();

        return [
            Stat::make('Orders today', $todayOrders)
                ->description($pending.' waiting to be confirmed')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending ? 'warning' : 'success'),
            Stat::make('Sales today', money($todayRevenue))
                ->description('Last 7 days')
                ->chart($trend)
                ->color('success'),
            Stat::make('Sales this month', money($monthRevenue))
                ->description(now()->format('F Y'))
                ->color('primary'),
            Stat::make('Table bookings', Reservation::where('status', 'pending')->count())
                ->description('Pending · '.Reservation::whereDate('date', today())->where('status', 'confirmed')->count().' confirmed today')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
            Stat::make('Reviews to approve', Review::where('is_approved', false)->count())
                ->description('Average rating '.number_format((float) Review::where('is_approved', true)->avg('rating'), 1).' ★')
                ->color('warning'),
        ];
    }
}
