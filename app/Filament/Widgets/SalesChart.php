<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Sales — last 14 days';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view orders');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn ($d) => today()->subDays($d));

        $totals = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', today()->subDays(13))
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'datasets' => [[
                'label' => 'Sales (₹)',
                'data' => $days->map(fn ($day) => (float) ($totals[$day->toDateString()] ?? 0))->all(),
                'backgroundColor' => '#c9a45c',
                'borderRadius' => 4,
            ]],
            'labels' => $days->map(fn ($day) => $day->format('d M'))->all(),
        ];
    }
}
