<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;

class SuperAdminService
{
    /**
     * Створює користувача (або оновлює наявного за email): активний, роль admin і всі наявні права.
     * Пароль змінюється лише якщо його передано.
     */
    public function grant(string $email, string $name, ?string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->is_active = true;

        if ($password !== null) {
            $user->password = $password;
        }

        $user->save();
        $user->syncRoles(User::ROLE_ADMIN);
        $user->syncPermissions(Permission::all());

        return $user;
    }
}
