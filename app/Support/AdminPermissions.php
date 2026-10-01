<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\User;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class AdminPermissions
{
    /**
     * Права для форм: розділ => [код => підпис].
     *
     * @return array<string, array<string, string>>
     */
    public static function catalog(): array
    {
        return Permission::orderBy('id')->get()->groupBy('group')
            ->map(fn ($permissions) => $permissions->pluck('title', 'name')->all())->all();
    }

    /** @param array<string, string> $operations */
    public static function crud(array $operations): void
    {
        foreach ($operations as $operation => $permission) {
            if (! backpack_user()?->can($permission)) {
                CRUD::denyAccess($operation);
            }
        }
    }

    public static function landingPath(User $user): ?string
    {
        foreach ([
            'licenses_view' => 'license', 'devices_view' => 'license-activation',
            'packages_view' => 'package', 'users_view' => 'user',
            'access_manage' => 'role', 'audit_view' => 'license-audit',
            'licenses_create' => 'license/create', 'packages_create' => 'package/create',
        ] as $permission => $path) {
            if ($user->can($permission)) {
                return $path;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function hiddenAuditEvents(User $user): array
    {
        return [
            ...($user->can('payments_view') ? [] : ['payment_recorded', 'renewed', 'payment_voided']),
            ...($user->can('devices_view') ? [] : ['device_activated', 'device_unbound']),
        ];
    }
}
