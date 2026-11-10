<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageCoupons;
use App\Models\Coupon;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'coupons';

    protected static ?string $model = Coupon::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Coupons & offers';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->required()->maxLength(40)->unique(ignoreRecord: true)
                ->placeholder('EID50')->helperText('Customers type this at checkout. Saved in CAPITALS.')
                ->regex('/^[A-Za-z0-9_-]+$/'),
            TextInput::make('description')->maxLength(150)->placeholder('Eid special offer'),
            Select::make('type')->options(['percent' => 'Percentage (%)', 'flat' => 'Flat amount (₹)'])->default('percent')->required()->native(false)->live(),
            TextInput::make('value')->numeric()->required()->minValue(1)->label('Discount value')
                ->maxValue(fn ($get) => $get('type') === 'percent' ? 100 : null),
            TextInput::make('min_order')->numeric()->prefix('₹')->default(0)->label('Minimum order'),
            TextInput::make('max_discount')->numeric()->prefix('₹')->label('Maximum discount')->helperText('Useful for % coupons. Empty = no cap.'),
            TextInput::make('usage_limit')->numeric()->minValue(1)->label('Total uses allowed')->helperText('Empty = unlimited.'),
            Toggle::make('is_active')->label('Active')->default(true)->inline(false),
            DateTimePicker::make('starts_at')->native(false)->seconds(false),
            DateTimePicker::make('expires_at')->native(false)->seconds(false)->after('starts_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->weight('bold')->copyable()->description(fn (Coupon $record) => $record->description),
                TextColumn::make('value')->label('Discount')->formatStateUsing(fn (Coupon $record) => $record->label)->badge()->color('success'),
                TextColumn::make('min_order')->money('INR')->label('Min order')->visibleFrom('md'),
                TextColumn::make('used_count')->label('Used')->formatStateUsing(fn (Coupon $record) => $record->used_count.($record->usage_limit ? ' / '.$record->usage_limit : '')),
                TextColumn::make('expires_at')->label('Expires')->dateTime('d M Y, h:i A')->placeholder('Never')->visibleFrom('md')
                    ->color(fn (Coupon $record) => $record->expires_at?->isPast() ? 'danger' : null),
                ToggleColumn::make('is_active')->label('Active')->disabled(fn () => ! static::can('update')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCoupons::route('/')];
    }
}
