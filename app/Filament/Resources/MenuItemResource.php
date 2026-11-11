<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageMenuItems;
use App\Filament\Support\Upload;
use App\Models\MenuItem;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class MenuItemResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'menu items';

    protected static ?string $model = MenuItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cake';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Dishes';

    protected static ?string $modelLabel = 'dish';

    protected static ?string $pluralModelLabel = 'dishes';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dish details')->schema([
                TextInput::make('name')->required()->maxLength(150),
                Select::make('category_id')->relationship('category', 'name')->required()->searchable()->preload(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                TextInput::make('price')->numeric()->required()->prefix('₹')->minValue(0),
                TextInput::make('sale_price')->numeric()->prefix('₹')->minValue(0)->label('Offer price')->helperText('Leave empty when there is no offer.'),
                TextInput::make('portion')->placeholder('Full / Half / 1 kg / 6 pcs')->maxLength(60),
                Select::make('spice_level')->options([0 => 'No spice', 1 => 'Mild', 2 => 'Medium', 3 => 'Spicy'])->default(1)->required(),
            ])->columns(2)->columnSpanFull(),

            Section::make('Photos')->schema([
                Upload::image('image', 'menu', 'Main photo'),
                Upload::image('photos', 'menu', 'More photos')->multiple()->reorderable()->maxFiles(6),
            ])->columns(2)->columnSpanFull(),

            Section::make('Visibility')->schema([
                Toggle::make('is_available')->label('Available today')->default(true),
                Toggle::make('is_veg')->label('Vegetarian'),
                Toggle::make('is_featured')->label('Signature dish (home page)'),
                Toggle::make('is_bestseller')->label('Bestseller badge'),
                TextInput::make('sort_order')->numeric()->default(0),
                TextInput::make('stock_qty')->label('Stock for today')->numeric()->minValue(0)
                    ->helperText('Leave empty for unlimited. With a number, each order reduces it and the dish shows "Sold out" at 0.'),
            ])->columns(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->getStateUsing(fn (MenuItem $record) => $record->image_url)->square()->imageSize(56),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn (MenuItem $record) => $record->portion),
                TextColumn::make('category.name')->badge()->sortable()->visibleFrom('md'),
                TextColumn::make('price')->money('INR')->sortable(),
                TextColumn::make('stock_qty')->label('Stock')->placeholder('∞')->sortable()->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray', $state === 0 => 'danger', $state <= 5 => 'warning', default => 'success'
                    }),
                TextColumn::make('sale_price')->money('INR')->label('Offer')->placeholder('—')->visibleFrom('lg'),
                ToggleColumn::make('is_available')->label('Available')->disabled(fn () => ! static::can('update')),
                ToggleColumn::make('is_featured')->label('Signature')->disabled(fn () => ! static::can('update')),
                ToggleColumn::make('is_bestseller')->label('Bestseller')->toggleable(isToggledHiddenByDefault: true)->disabled(fn () => ! static::can('update')),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name'),
                TernaryFilter::make('is_available')->label('Available'),
                TernaryFilter::make('is_featured')->label('Signature'),
                TernaryFilter::make('is_veg')->label('Vegetarian'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('available')->label('Mark available')->icon('heroicon-o-check-circle')
                        ->visible(fn () => static::can('update'))
                        ->action(fn (Collection $records) => $records->each->update(['is_available' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('unavailable')->label('Mark sold out')->icon('heroicon-o-x-circle')->color('warning')
                        ->visible(fn () => static::can('update'))
                        ->action(fn (Collection $records) => $records->each->update(['is_available' => false]))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMenuItems::route('/')];
    }
}
