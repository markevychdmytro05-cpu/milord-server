<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 16)->unique();
            $table->string('name');
            $table->json('permissions');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $all = [
            'dashboard_view', 'licenses_view', 'licenses_create', 'licenses_update',
            'licenses_delete', 'license_history_view', 'devices_view', 'devices_unbind',
            'payments_view', 'payments_create', 'payments_void', 'packages_view',
            'packages_create', 'packages_update', 'packages_delete', 'audit_view',
            'users_view', 'access_manage',
        ];
        $manager = [
            'dashboard_view', 'licenses_view', 'licenses_create', 'licenses_update',
            'license_history_view', 'devices_view', 'devices_unbind', 'payments_view', 'payments_create',
        ];
        foreach ([['admin', 'Адміністратор', $all], ['manager', 'Менеджер', $manager]] as [$slug, $name, $permissions]) {
            DB::table('roles')->insert([
                'slug' => $slug, 'name' => $name, 'permissions' => json_encode($permissions),
                'is_system' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->json('permission_overrides')->nullable();
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('permission_overrides');
        });
        Schema::dropIfExists('roles');
    }
};
