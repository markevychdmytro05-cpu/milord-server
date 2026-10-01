<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'layouts_view' => 'Перегляд шаблонів сторінок',
        'layouts_create' => 'Створення шаблонів сторінок',
        'layouts_update' => 'Редагування шаблонів сторінок',
        'layouts_delete' => 'Видалення шаблонів сторінок',
        'modules_view' => 'Перегляд модулів сайту',
        'modules_create' => 'Створення модулів сайту',
        'modules_update' => 'Редагування модулів сайту',
        'modules_delete' => 'Видалення модулів сайту',
    ];

    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('layout_id')->nullable()->after('type')
                ->constrained('layouts')->cascadeOnUpdate()->nullOnDelete();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->foreignId('footer_layout_id')->nullable()->after('footer_links')
                ->constrained('layouts')->cascadeOnUpdate()->nullOnDelete();
        });

        $pagesView = DB::table('permissions')->where('name', 'pages_view')->value('id');
        $roleIds = $pagesView ? DB::table('role_has_permissions')->where('permission_id', $pagesView)->pluck('role_id') : collect();

        foreach (self::PERMISSIONS as $name => $title) {
            $id = DB::table('permissions')->insertGetId([
                'name' => $name, 'title' => $title, 'group' => 'Сайт', 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->delete();

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('footer_layout_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('layout_id');
        });
    }
};
