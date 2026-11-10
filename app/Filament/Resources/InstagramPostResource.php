<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageInstagramPosts;
use App\Filament\Support\Upload;
use App\Models\InstagramPost;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class InstagramPostResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'instagram';

    protected static ?string $model = InstagramPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-camera';

    protected static string|\UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Instagram feed';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Upload::image('image', 'instagram', 'Post image / reel cover')->required()->columnSpanFull(),
            TextInput::make('permalink')->label('Instagram post link')->url()->columnSpanFull()
                ->placeholder('https://www.instagram.com/p/…'),
            Textarea::make('caption')->rows(3)->columnSpanFull(),
            Select::make('media_type')->options(['IMAGE' => 'Photo', 'VIDEO' => 'Reel / video', 'CAROUSEL_ALBUM' => 'Carousel'])->default('IMAGE')->required()->native(false),
            DateTimePicker::make('posted_at')->native(false),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->label('Visible')->default(true)->inline(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')->getStateUsing(fn (InstagramPost $record) => $record->image_url)->square()->imageSize(72),
                TextColumn::make('caption')->limit(70)->wrap()->searchable()->placeholder('—'),
                TextColumn::make('media_type')->badge()->color('gray')->visibleFrom('md'),
                TextColumn::make('instagram_id')->label('Synced')->formatStateUsing(fn () => 'Auto')->badge()->color('success')->placeholder('Manual'),
                TextColumn::make('posted_at')->date('d M Y')->sortable()->placeholder('—')->visibleFrom('md'),
                ToggleColumn::make('is_active')->label('Visible')->disabled(fn () => ! static::can('update')),
            ])
            ->defaultSort('posted_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageInstagramPosts::route('/')];
    }
}
