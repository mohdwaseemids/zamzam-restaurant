<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageContactMessages;
use App\Models\ContactMessage;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'messages';

    protected static ?string $model = ContactMessage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|\UnitEnum|null $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Messages';

    protected static ?string $modelLabel = 'message';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::where('is_read', false)->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('phone')->copyable(),
            TextEntry::make('email')->placeholder('—'),
            TextEntry::make('created_at')->label('Received')->dateTime('d M Y, h:i A'),
            TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
            TextEntry::make('message')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_read')->label('Read')->boolean(),
                TextColumn::make('name')->searchable()->weight('bold')->description(fn (ContactMessage $record) => $record->phone),
                TextColumn::make('subject')->placeholder('—')->limit(40)->visibleFrom('md'),
                TextColumn::make('message')->limit(70)->wrap(),
                TextColumn::make('created_at')->label('Received')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_read')->label('Read')])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()->mountUsing(fn (ContactMessage $record) => $record->is_read ?: $record->update(['is_read' => true])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactMessages::route('/')];
    }
}
