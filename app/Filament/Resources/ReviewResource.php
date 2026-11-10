<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageReviews;
use App\Filament\Support\Upload;
use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'reviews';

    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Review::where('is_approved', false)->count();

        return $count ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('customer_name')->required()->maxLength(100),
            TextInput::make('city')->maxLength(100)->placeholder('Kairana'),
            TextInput::make('phone')->tel()->maxLength(20),
            Select::make('rating')->options([5 => '★★★★★  5', 4 => '★★★★  4', 3 => '★★★  3', 2 => '★★  2', 1 => '★  1'])->default(5)->required()->native(false),
            Select::make('menu_item_id')->label('Dish reviewed')->relationship('menuItem', 'name')->searchable()->preload(),
            Select::make('source')->options(['website' => 'Website', 'google' => 'Google', 'instagram' => 'Instagram'])->default('website')->required()->native(false),
            Textarea::make('comment')->required()->rows(4)->columnSpanFull(),
            Upload::image('photo', 'reviews', 'Customer / food photo')->columnSpanFull(),
            Toggle::make('is_approved')->label('Approved (visible on website)')->default(true),
            Toggle::make('is_featured')->label('Show on home page'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')->label('Customer')->searchable()->weight('bold')->description(fn (Review $record) => $record->city),
                TextColumn::make('rating')->formatStateUsing(fn (int $state) => str_repeat('★', $state))->color('warning')->sortable(),
                TextColumn::make('comment')->limit(70)->wrap(),
                TextColumn::make('menuItem.name')->label('Dish')->placeholder('—')->toggleable()->visibleFrom('lg'),
                TextColumn::make('source')->badge()->color('gray')->visibleFrom('lg'),
                ToggleColumn::make('is_approved')->label('Approved')->disabled(fn () => ! static::can('update')),
                ToggleColumn::make('is_featured')->label('Home page')->disabled(fn () => ! static::can('update')),
                TextColumn::make('created_at')->since()->sortable()->visibleFrom('md'),
            ])
            ->filters([
                TernaryFilter::make('is_approved')->label('Approved'),
                TernaryFilter::make('is_featured')->label('Featured'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn (Review $record) => ! $record->is_approved && static::can('update'))
                    ->action(fn (Review $record) => $record->update(['is_approved' => true])),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
