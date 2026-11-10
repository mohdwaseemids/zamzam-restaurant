<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageOrders;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'orders';

    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'customer_name', 'phone'];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Order::where('status', 'pending')->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** Edit form: status and payment only. New counter orders use their own form (ManageOrders). */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->options(Order::STATUSES)->required()->native(false),
            Select::make('payment_status')->options(Order::PAYMENT_STATUSES)->required()->native(false),
            Textarea::make('notes')->label('Order notes')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function infolist(Schema $schema): Schema
    {
        $paymentColor = fn (string $state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'gray', default => 'warning' };

        return $schema->components([
            Section::make('Order')->schema([
                TextEntry::make('order_number')->label('Order #')->weight('bold')->copyable(),
                TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, h:i A'),
                TextEntry::make('status')->badge()->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => Order::STATUS_COLORS[$state] ?? 'gray'),
                TextEntry::make('type')->formatStateUsing(fn (string $state) => Order::TYPES[$state] ?? $state),
                TextEntry::make('payment_method')->formatStateUsing(fn (string $state) => Order::PAYMENT_METHODS[$state] ?? $state),
                TextEntry::make('payment_status')->badge()->formatStateUsing(fn (string $state) => Order::PAYMENT_STATUSES[$state] ?? $state)->color($paymentColor),
                TextEntry::make('razorpay_payment_id')->label('Razorpay payment ID')->placeholder('—')->copyable(),
                TextEntry::make('source')->formatStateUsing(fn (string $state) => ucfirst($state)),
            ])->columns(['default' => 2, 'lg' => 4])->columnSpanFull(),

            Section::make('Customer')->schema([
                TextEntry::make('customer_name')->label('Name'),
                TextEntry::make('phone')->copyable()->placeholder('—'),
                TextEntry::make('address')->placeholder('—')->columnSpanFull(),
                TextEntry::make('landmark')->placeholder('—'),
                TextEntry::make('notes')->placeholder('—'),
            ])->columns(['default' => 2])->columnSpanFull(),

            Section::make('Items')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->schema([
                    TextEntry::make('name')->hiddenLabel()->weight('bold')->columnSpan(2),
                    TextEntry::make('quantity')->hiddenLabel()->prefix('× '),
                    TextEntry::make('total')->hiddenLabel()->money('INR'),
                ])->columns(4)->columnSpanFull(),
                TextEntry::make('subtotal')->money('INR'),
                TextEntry::make('discount')->money('INR')->label(fn (Order $record) => $record->coupon_code ? 'Discount ('.$record->coupon_code.')' : 'Discount'),
                TextEntry::make('delivery_charge')->money('INR'),
                TextEntry::make('total')->money('INR')->weight('bold')->size('lg'),
            ])->columns(['default' => 2, 'lg' => 4])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')->label('Order #')->searchable()->weight('bold')
                    ->wrap()
                    ->description(fn (Order $record) => $record->customer_name.' · '.money($record->total).' · '.$record->created_at->format('d M, h:i A')),
                TextColumn::make('customer_name')->label('Customer')->searchable()->description(fn (Order $record) => $record->phone)->visibleFrom('md'),
                TextColumn::make('phone')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')->badge()->color('gray')->formatStateUsing(fn (string $state) => Order::TYPES[$state] ?? $state)->visibleFrom('md'),
                TextColumn::make('items_count')->counts('items')->label('Items')->visibleFrom('lg'),
                TextColumn::make('total')->money('INR')->sortable()->weight('bold')->visibleFrom('sm'),
                TextColumn::make('payment_status')->label('Payment')->badge()->formatStateUsing(fn (string $state) => Order::PAYMENT_STATUSES[$state] ?? $state)
                    ->description(fn (Order $record) => Order::PAYMENT_METHODS[$record->payment_method] ?? $record->payment_method)
                    ->color(fn (string $state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'gray', default => 'warning' })
                    ->visibleFrom('md'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => Order::STATUS_COLORS[$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('payment_status')->options(Order::PAYMENT_STATUSES),
                SelectFilter::make('type')->options(Order::TYPES),
                SelectFilter::make('source')->options(['web' => 'Website', 'counter' => 'Counter', 'app' => 'App']),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->recordActions([
                // One click to push the order to its next step.
                Action::make('advance')
                    ->label(fn (Order $record) => 'Mark '.strtolower(Order::STATUSES[$record->nextStatus()] ?? ''))
                    ->icon('heroicon-m-arrow-right-circle')
                    ->color('success')
                    ->button()
                    ->labeledFrom('md')
                    ->tooltip(fn (Order $record) => 'Mark '.strtolower(Order::STATUSES[$record->nextStatus()] ?? ''))
                    ->visible(fn (Order $record) => $record->nextStatus() !== null && static::can('update', $record))
                    ->action(fn (Order $record) => $record->moveTo($record->nextStatus())),

                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('bill')->label('Print bill')->icon('heroicon-o-printer')
                        ->url(fn (Order $record) => route('admin.orders.bill', $record), shouldOpenInNewTab: true),
                    EditAction::make()->label('Change status'),
                    Action::make('cancel')->label('Cancel order')->icon('heroicon-o-x-circle')->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record) => ! in_array($record->status, ['completed', 'cancelled']) && static::can('update', $record))
                        ->action(fn (Order $record) => $record->update(['status' => 'cancelled'])),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrders::route('/')];
    }
}
