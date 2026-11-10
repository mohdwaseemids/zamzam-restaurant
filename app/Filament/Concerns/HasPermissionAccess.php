<?php

namespace App\Filament\Concerns;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Maps every Filament resource ability onto a Spatie permission:
 * "view {key}", "create {key}", "update {key}", "delete {key}".
 *
 * Filament routes every check (page access, navigation, buttons, bulk actions)
 * through getAuthorizationResponse(), so this is the single gate for a resource.
 *
 * The resource must define: protected static string $permissionKey = 'orders';
 */
trait HasPermissionAccess
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        return static::permissionAllows($action, $record)
            ? Response::allow()
            : Response::deny('You do not have permission to do this.');
    }

    protected static function permissionAllows(string|UnitEnum $action, ?Model $record = null): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        $action = $action instanceof UnitEnum ? $action->name : $action;

        $ability = match ($action) {
            'viewAny', 'view' => 'view',
            'create', 'replicate' => 'create',
            'update', 'reorder' => 'update',
            default => 'delete', // delete, deleteAny, forceDelete, forceDeleteAny, restore, restoreAny
        };

        return $user->can($ability.' '.static::$permissionKey);
    }
}
