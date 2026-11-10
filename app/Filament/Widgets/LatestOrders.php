<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 1];

    protected static ?string $heading = 'Latest orders';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('view orders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest()->limit(6))
            ->paginated(false)
            ->poll('30s')
            ->recordUrl(fn () => OrderResource::getUrl())
            ->emptyStateHeading('No orders yet')
            ->columns([
                TextColumn::make('order_number')->label('Order')->weight('bold')->description(fn (Order $record) => $record->created_at->diffForHumans()),
                TextColumn::make('customer_name')->label('Customer')->visibleFrom('md'),
                TextColumn::make('total')->money('INR'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => Order::STATUS_COLORS[$state] ?? 'gray'),
            ]);
    }
}
