<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    protected const ACTIVE = ['confirmed', 'preparing', 'out_for_delivery', 'ready'];

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'new' => Tab::make('New')
                ->badge(Order::where('status', 'pending')->count() ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'active' => Tab::make('In progress')
                ->badge(Order::whereIn('status', self::ACTIVE)->count() ?: null)->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', self::ACTIVE)),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Walk-in, phone and dine-in orders typed in by staff.
            CreateAction::make()
                ->label('New counter order')
                ->icon('heroicon-m-plus')
                ->modalHeading('New counter order')
                ->modalSubmitActionLabel('Create order')
                ->createAnother(false)
                ->schema([
                    Select::make('type')->label('Order type')->options(Order::TYPES)->default('dine_in')->required()->native(false)->live(),
                    Select::make('payment_method')->options(['cod' => 'Cash', 'counter_upi' => 'UPI / card at counter'])->default('cod')->required()->native(false),
                    TextInput::make('customer_name')->default('Walk-in customer')->required()->maxLength(100),
                    TextInput::make('phone')->tel()->maxLength(20)->required(fn (Get $get) => $get('type') === 'delivery'),
                    Textarea::make('address')->rows(2)->columnSpanFull()
                        ->visible(fn (Get $get) => $get('type') === 'delivery')
                        ->required(fn (Get $get) => $get('type') === 'delivery'),

                    Repeater::make('items')->label('Dishes')->columnSpanFull()->minItems(1)->defaultItems(1)
                        ->addActionLabel('Add another dish')
                        ->schema([
                            Select::make('menu_item_id')->label('Dish')->required()->searchable()->columnSpan(3)
                                ->options(fn () => MenuItem::available()->orderBy('name')->get()
                                    ->mapWithKeys(fn (MenuItem $item) => [$item->id => $item->name.($item->portion ? ' · '.$item->portion : '').' — '.money($item->final_price)]))
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                            TextInput::make('quantity')->label('Qty')->numeric()->default(1)->minValue(1)->maxValue(99)->required(),
                        ])->columns(4),

                    TextInput::make('discount')->numeric()->prefix('₹')->default(0)->minValue(0),
                    Toggle::make('paid')->label('Payment received')->default(true)->inline(false),
                    Textarea::make('notes')->label('Kitchen notes')->rows(2)->columnSpanFull(),
                ])
                ->using(fn (array $data): Order => $this->createCounterOrder($data)),
        ];
    }

    protected function createCounterOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $quantities = collect($data['items'])->mapWithKeys(fn ($row) => [(int) $row['menu_item_id'] => (int) $row['quantity']]);
            $dishes = MenuItem::whereIn('id', $quantities->keys())->get();

            $lines = $dishes->map(fn (MenuItem $dish) => [
                'menu_item_id' => $dish->id,
                'name' => $dish->name.($dish->portion ? ' ('.$dish->portion.')' : ''),
                'price' => $dish->final_price,
                'quantity' => $quantities[$dish->id],
                'total' => round($dish->final_price * $quantities[$dish->id], 2),
            ]);

            app(OrderService::class)->takeStock($lines->map(fn (array $line): array => [
                'id' => $line['menu_item_id'], 'name' => $line['name'], 'quantity' => $line['quantity'],
            ])->all());

            $subtotal = (float) $lines->sum('total');
            $discount = min($subtotal, (float) ($data['discount'] ?? 0));
            $delivery = $data['type'] === 'delivery' ? (float) setting('delivery_charge', 0) : 0.0;
            $tax = round(($subtotal - $discount) * ((float) setting('tax_percent', 0)) / 100, 2);
            $paid = (bool) ($data['paid'] ?? false);

            $order = Order::create([
                'order_number' => Order::generateNumber(),
                'token' => Str::random(48),
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'],
                'phone' => $data['phone'] ?? '',
                'type' => $data['type'],
                'address' => $data['type'] === 'delivery' ? ($data['address'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_charge' => $delivery,
                'tax' => $tax,
                'total' => round($subtotal - $discount + $delivery + $tax, 2),
                'payment_method' => $data['payment_method'],
                'payment_status' => $paid ? 'paid' : 'pending',
                'paid_at' => $paid ? now() : null,
                'status' => 'confirmed',
                'source' => 'counter',
            ]);

            $order->items()->createMany($lines->all());

            return $order;
        });
    }
}
