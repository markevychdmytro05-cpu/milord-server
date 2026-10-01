<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GUARD = 'web';

    private const USER_MODEL = 'App\Models\User';

    /** @return array<string, array<string, string>> */
    private function catalog(): array
    {
        return [
            'Огляд' => ['dashboard_view' => 'Перегляд головної сторінки'],
            'Ліцензії' => [
                'licenses_view' => 'Перегляд ліцензій',
                'licenses_create' => 'Створення ліцензій',
                'licenses_update' => 'Редагування й відкликання ліцензій',
                'licenses_delete' => 'Видалення ліцензій',
                'license_history_view' => 'Перегляд історії ліцензії',
            ],
            'Пристрої' => [
                'devices_view' => 'Перегляд активацій та пристроїв',
                'devices_unbind' => 'Відв’язування та скидання пристроїв',
            ],
            'Оплати' => [
                'payments_view' => 'Перегляд оплат і фінансових показників',
                'payments_create' => 'Запис оплат і продовження ліцензій',
                'payments_void' => 'Скасування записів оплат',
            ],
            'Пакети' => [
                'packages_view' => 'Перегляд тарифних пакетів',
                'packages_create' => 'Створення пакетів',
                'packages_update' => 'Редагування та перемикання пакетів',
                'packages_delete' => 'Видалення пакетів',
            ],
            'Адміністрування' => [
                'audit_view' => 'Перегляд загального журналу ліцензій',
                'users_view' => 'Перегляд користувачів адмінки',
                'access_manage' => 'Керування користувачами, ролями та правами',
            ],
        ];
    }

    public function up(): void
    {
        $denied = DB::table('users')->whereNotNull('permission_overrides')->get()
            ->filter(fn (object $user): bool => in_array('deny', json_decode($user->permission_overrides, true) ?: [], true));
        if ($denied->isNotEmpty()) {
            throw new RuntimeException('Особисті заборони більше не підтримуються. Приберіть їх у користувачів (id: '.$denied->pluck('id')->implode(', ').') і повторіть міграцію.');
        }

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->string('group');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        $permissionIds = [];
        foreach ($this->catalog() as $group => $permissions) {
            foreach ($permissions as $name => $title) {
                $permissionIds[$name] = DB::table('permissions')->insertGetId([
                    'name' => $name, 'title' => $title, 'group' => $group, 'guard_name' => self::GUARD,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('name', 'title');
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->renameColumn('slug', 'name');
            $table->string('guard_name')->default(self::GUARD);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
        foreach (['model_has_roles' => 'role_id', 'model_has_permissions' => 'permission_id'] as $pivot => $key) {
            Schema::create($pivot, function (Blueprint $table) use ($pivot, $key) {
                $table->foreignId($key)->constrained($pivot === 'model_has_roles' ? 'roles' : 'permissions')->cascadeOnDelete();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary([$key, 'model_id', 'model_type']);
            });
        }

        $roleIds = [];
        foreach (DB::table('roles')->get() as $role) {
            $roleIds[$role->name] = $role->id;
            foreach (json_decode($role->permissions, true) ?: [] as $name) {
                if (isset($permissionIds[$name])) {
                    DB::table('role_has_permissions')->insert(['permission_id' => $permissionIds[$name], 'role_id' => $role->id]);
                }
            }
        }

        foreach (DB::table('users')->get() as $user) {
            if (isset($roleIds[$user->role])) {
                DB::table('model_has_roles')->insert(['role_id' => $roleIds[$user->role], 'model_type' => self::USER_MODEL, 'model_id' => $user->id]);
            }
            foreach (json_decode($user->permission_overrides ?? '[]', true) ?: [] as $name => $choice) {
                if ($choice === 'allow' && isset($permissionIds[$name])) {
                    DB::table('model_has_permissions')->insert(['permission_id' => $permissionIds[$name], 'model_type' => self::USER_MODEL, 'model_id' => $user->id]);
                }
            }
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'permission_overrides']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Міграцію на spatie/laravel-permission не можна відкотити.');
    }
};
