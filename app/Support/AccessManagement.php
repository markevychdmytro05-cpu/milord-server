<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

class AccessManagement
{
    /**
     * @template T
     *
     * @param  callable(User): T  $change
     * @return T
     */
    public static function run(callable $change): mixed
    {
        try {
            return self::runInTransaction($change);
        } catch (Throwable $exception) {
            // Відкат транзакції не скидає кеш прав, який міг перезаповнитися незакомічними даними.
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            throw $exception;
        }
    }

    /**
     * @template T
     *
     * @param  callable(User): T  $change
     * @return T
     */
    private static function runInTransaction(callable $change): mixed
    {
        return DB::transaction(function () use ($change): mixed {
            // This permanent row serializes access changes before checking the current actor.
            Role::where('name', User::ROLE_ADMIN)->lockForUpdate()->firstOrFail();
            $actor = User::find(backpack_user()->id);
            Gate::forUser($actor)->authorize('access_manage');
            $result = $change($actor);

            $hasAccessManager = User::where('is_active', true)->with('roles')->get()
                ->contains(fn (User $user): bool => $user->can('access_manage'));
            if (! $hasAccessManager) {
                throw ValidationException::withMessages([
                    'role' => 'Має залишитися хоча б один активний користувач із правом керування доступом.',
                ]);
            }

            return $result;
        }, attempts: 3);
    }
}
