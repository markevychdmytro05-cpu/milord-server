<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('page');
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->default(false);
            $table->boolean('is_published')->default(false);
            $table->boolean('show_in_header')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['type', 'slug']);
        });

        $permissions = [
            'pages_view' => 'Перегляд сторінок сайту',
            'pages_create' => 'Створення сторінок сайту',
            'pages_update' => 'Редагування сторінок сайту',
            'pages_delete' => 'Видалення сторінок сайту',
        ];

        $siteManage = DB::table('permissions')->where('name', 'site_manage')->value('id');
        $roleIds = $siteManage ? DB::table('role_has_permissions')->where('permission_id', $siteManage)->pluck('role_id') : collect();

        foreach ($permissions as $name => $title) {
            $id = DB::table('permissions')->insertGetId([
                'name' => $name, 'title' => $title, 'group' => 'Сайт', 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }

        DB::table('permissions')->where('name', 'site_manage')->update(['group' => 'Сайт']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', ['pages_view', 'pages_create', 'pages_update', 'pages_delete'])->delete();
        Schema::dropIfExists('pages');
    }
};
