<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageVideos;
use App\Filament\Support\Upload;
use App\Models\Video;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VideoResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'videos';

    protected static ?string $model = Video::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-play-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Videos & live reviews';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(150)->columnSpanFull(),
            Select::make('type')->label('Video source')->options(Video::TYPES)->default('youtube')->required()->live()->native(false),
            Select::make('category')->options(Video::CATEGORIES)->default('review')->required()->native(false),
            TextInput::make('url')->label('Video link')->url()->columnSpanFull()
                ->placeholder('https://www.youtube.com/watch?v=…  or  https://www.instagram.com/reel/…')
                ->helperText('YouTube videos, Shorts and Live links all work.')
                ->visible(fn (Get $get) => in_array($get('type'), ['youtube', 'instagram']))
                ->required(fn (Get $get) => in_array($get('type'), ['youtube', 'instagram'])),
            Upload::video('video_file', 'videos')->columnSpanFull()
                ->visible(fn (Get $get) => $get('type') === 'upload')
                ->required(fn (Get $get) => $get('type') === 'upload'),
            Upload::image('thumbnail', 'videos/thumbnails', 'Cover photo')->columnSpanFull()
                ->helperText('Optional for YouTube (its thumbnail is used automatically).'),
            TextInput::make('customer_name')->label('Customer name')->placeholder('For review videos'),
            TextInput::make('sort_order')->numeric()->default(0),
            Textarea::make('description')->rows(2)->columnSpanFull(),
            Toggle::make('is_featured')->label('Show on home page')->default(true),
            Toggle::make('is_active')->label('Visible')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')->getStateUsing(fn (Video $record) => $record->thumbnail_url)->imageHeight(54)->imageWidth(96),
                TextColumn::make('title')->searchable()->weight('bold')->description(fn (Video $record) => $record->customer_name),
                TextColumn::make('type')->badge()->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) { 'youtube' => 'danger', 'instagram' => 'primary', default => 'gray' }),
                TextColumn::make('category')->badge()->color('gray')->formatStateUsing(fn (string $state) => Video::CATEGORIES[$state] ?? $state)->visibleFrom('md'),
                ToggleColumn::make('is_featured')->label('Home page')->disabled(fn () => ! static::can('update')),
                ToggleColumn::make('is_active')->label('Visible')->disabled(fn () => ! static::can('update')),
            ])
            ->filters([
                SelectFilter::make('type')->options(Video::TYPES),
                SelectFilter::make('category')->options(Video::CATEGORIES),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVideos::route('/')];
    }
}
