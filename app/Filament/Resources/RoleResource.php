<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Pages\ManageRoles;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    use HasPermissionAccess {
        permissionAllows as protected basePermissionAllows;
    }

    protected static string $permissionKey = 'roles';

    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Roles & permissions';

    /** The built-in super_admin role always has every permission and cannot be changed. */
    protected static function permissionAllows(string|\UnitEnum $action, ?Model $record = null): bool
    {
        if ($record instanceof Role && $record->name === 'super_admin' && in_array($action, ['update', 'delete', 'forceDelete'])) {
            return false;
        }

        return static::basePermissionAllows($action, $record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(60)->unique(ignoreRecord: true)
                ->helperText('Use lowercase with underscores, e.g. kitchen_staff'),
            CheckboxList::make('permissions')
                ->relationship('permissions', 'name')
                ->getOptionLabelFromRecordUsing(fn (Model $record) => ucfirst($record->name))
                ->columns(4)
                ->bulkToggleable()
                ->searchable(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold')->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                TextColumn::make('permissions_count')->counts('permissions')->label('Permissions')->badge()
                    ->formatStateUsing(fn (Role $record, $state) => $record->name === 'super_admin' ? 'All' : $state),
                TextColumn::make('users_count')->counts('users')->label('Users')->badge()->color('gray'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRoles::route('/')];
    }
}
