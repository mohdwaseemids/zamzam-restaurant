<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageGalleryImages;
use App\Filament\Support\Upload;
use App\Models\GalleryImage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GalleryImageResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'gallery';

    protected static ?string $model = GalleryImage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Gallery';

    protected static ?string $modelLabel = 'photo';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Upload::image('image', 'gallery')->required()->columnSpanFull(),
            TextInput::make('title')->maxLength(150),
            Select::make('category')->options(GalleryImage::CATEGORIES)->default('food')->required()->native(false),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_featured')->label('Show on home page')->inline(false),
            Toggle::make('is_active')->label('Visible')->default(true)->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->getStateUsing(fn (GalleryImage $record) => $record->image_url)->imageSize(72)->square(),
                TextColumn::make('title')->searchable()->placeholder('Untitled'),
                TextColumn::make('category')->badge()->formatStateUsing(fn (string $state) => GalleryImage::CATEGORIES[$state] ?? $state),
                ToggleColumn::make('is_featured')->label('Home page')->disabled(fn () => ! static::can('update')),
                ToggleColumn::make('is_active')->label('Visible')->disabled(fn () => ! static::can('update')),
                TextColumn::make('created_at')->date('d M Y')->sortable()->visibleFrom('md'),
            ])
            ->filters([SelectFilter::make('category')->options(GalleryImage::CATEGORIES)])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageGalleryImages::route('/')];
    }
}
