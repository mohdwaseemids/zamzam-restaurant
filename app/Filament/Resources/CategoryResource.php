<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageCategories;
use App\Filament\Support\Upload;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'categories';

    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('tagline')->maxLength(150)->placeholder('Slow cooked on charcoal'),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Upload::image('image', 'categories', 'Category photo')->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower number shows first.'),
            Toggle::make('is_active')->label('Show on website')->default(true)->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->getStateUsing(fn (Category $record) => $record->image_url)->square()->imageSize(56),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn (Category $record) => $record->tagline),
                TextColumn::make('menu_items_count')->counts('menuItems')->label('Dishes')->badge(),
                TextColumn::make('sort_order')->label('Order')->sortable()->visibleFrom('md'),
                ToggleColumn::make('is_active')->label('Visible')->disabled(fn () => ! static::can('update')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
