<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageDeliveryAreas;
use App\Models\DeliveryArea;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DeliveryAreaResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'delivery areas';

    protected static ?string $model = DeliveryArea::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Area / mohalla name')->required()->maxLength(100)->placeholder('Al Kalan'),
            TextInput::make('pincode')->maxLength(10)->placeholder('247774'),
            TextInput::make('charge')->label('Delivery charge')->numeric()->prefix('₹')->default(0)->minValue(0)->required(),
            TextInput::make('min_order')->label('Minimum order for this area')->numeric()->prefix('₹')->default(0)->minValue(0)
                ->helperText('0 = use the normal minimum order.'),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->label('We deliver here')->default(true)->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('When at least one area is active, customers must pick their area at checkout and addresses outside the list cannot order delivery.')
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold')->description(fn (DeliveryArea $record): ?string => $record->pincode),
                TextColumn::make('charge')->money('INR')->label('Charge')->sortable(),
                TextColumn::make('min_order')->money('INR')->label('Min order')->visibleFrom('md'),
                ToggleColumn::make('is_active')->label('Active')->disabled(fn (): bool => ! static::can('update')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeliveryAreas::route('/')];
    }
}
