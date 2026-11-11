<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageRestaurantTables;
use App\Models\RestaurantTable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class RestaurantTableResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'tables';

    protected static ?string $model = RestaurantTable::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Tables & QR codes';

    protected static ?string $modelLabel = 'table';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(40)->placeholder('Table 5 / Family Hall 2'),
            TextInput::make('seats')->numeric()->default(4)->minValue(1)->maxValue(100)->required(),
            Toggle::make('is_active')->label('QR code active')->default(true)->helperText('Switch off to stop orders from this table without reprinting the code.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Print the QR code and place it on the table. Guests scan it, order from their phone and the order arrives here marked with the table name.')
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('seats')->badge()->color('gray'),
                TextColumn::make('order_url')->label('Scan link')->copyable()->limit(40)->visibleFrom('lg'),
                ToggleColumn::make('is_active')->label('Active')->disabled(fn (): bool => ! static::can('update')),
            ])
            ->defaultSort('name')
            ->recordActions([
                Action::make('qr')->label('Print QR')->icon('heroicon-o-printer')->color('success')
                    ->url(fn (RestaurantTable $record): string => route('admin.tables.qr', $record), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRestaurantTables::route('/')];
    }
}
