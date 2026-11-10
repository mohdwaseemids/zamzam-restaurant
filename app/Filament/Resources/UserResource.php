<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    use HasPermissionAccess {
        permissionAllows as protected basePermissionAllows;
    }

    protected static string $permissionKey = 'users';

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Staff & users';

    protected static ?string $recordTitleAttribute = 'name';

    /** Only a super admin may edit or delete another super admin; nobody deletes themself. */
    protected static function permissionAllows(string|\UnitEnum $action, ?Model $record = null): bool
    {
        if (! static::basePermissionAllows($action, $record)) {
            return false;
        }

        if ($record instanceof User && in_array($action, ['update', 'delete', 'forceDelete'])) {
            if ($record->hasRole('super_admin') && ! auth()->user()->hasRole('super_admin')) {
                return false;
            }

            if ($action !== 'update' && $record->is(auth()->user())) {
                return false;
            }
        }

        return true;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('email')->email()->maxLength(150)->unique(ignoreRecord: true)
                ->required(fn ($get) => blank($get('phone')))->helperText('Staff sign in to this panel with their email.'),
            TextInput::make('phone')->tel()->maxLength(20)->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (?string $state) => User::normalizePhone($state)),
            TextInput::make('password')->password()->revealable()
                ->rule(Password::min(8))
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave empty to keep the current password.' : null),
            Select::make('roles')->multiple()->preload()->columnSpanFull()
                ->relationship('roles', 'name', fn (Builder $query) => auth()->user()->hasRole('super_admin') ? $query : $query->where('name', '!=', 'super_admin'))
                ->getOptionLabelFromRecordUsing(fn (Model $record) => str($record->name)->replace('_', ' ')->title()),
            Toggle::make('is_active')->label('Account active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn (User $record) => $record->email ?: $record->phone),
                TextColumn::make('email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')->placeholder('—')->visibleFrom('md'),
                TextColumn::make('roles.name')->badge()->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                ToggleColumn::make('is_active')->label('Active')
                    ->disabled(fn (User $record) => $record->is(auth()->user()) || ! static::can('update', $record)),
                TextColumn::make('created_at')->label('Joined')->date('d M Y')->sortable()->visibleFrom('md'),
            ])
            ->filters([SelectFilter::make('roles')->relationship('roles', 'name')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
