<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageReservations;
use App\Models\Reservation;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    use HasPermissionAccess;

    protected static string $permissionKey = 'reservations';

    protected static ?string $model = Reservation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Restaurant';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Table bookings';

    protected static ?string $modelLabel = 'table booking';

    public static function getNavigationBadge(): ?string
    {
        $count = Reservation::where('status', 'pending')->count();

        return $count ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('phone')->tel()->required()->maxLength(20),
            TextInput::make('email')->email()->maxLength(150),
            TextInput::make('guests')->numeric()->required()->minValue(1)->maxValue(500)->default(2),
            DatePicker::make('date')->required()->native(false),
            TextInput::make('time')->required()->placeholder('20:00'),
            TextInput::make('occasion')->placeholder('Birthday, family dinner, party…'),
            Select::make('status')->options(Reservation::STATUSES)->default('pending')->required()->native(false),
            Textarea::make('message')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold')->description(fn (Reservation $record) => $record->phone),
                TextColumn::make('date')->date('D, d M Y')->sortable()->description(fn (Reservation $record) => $record->time),
                TextColumn::make('guests')->badge(),
                TextColumn::make('occasion')->placeholder('—')->visibleFrom('md'),
                TextColumn::make('message')->limit(40)->placeholder('—')->toggleable()->visibleFrom('lg'),
                SelectColumn::make('status')->options(Reservation::STATUSES)->disabled(fn () => ! static::can('update')),
                TextColumn::make('created_at')->label('Requested')->since()->sortable()->visibleFrom('lg'),
            ])
            ->filters([SelectFilter::make('status')->options(Reservation::STATUSES)])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReservations::route('/')];
    }
}
