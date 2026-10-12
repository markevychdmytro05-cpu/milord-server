<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'releases_view' => 'Перегляд релізів програми',
        'releases_update' => 'Редагування й публікація релізів програми',
        'releases_delete' => 'Видалення релізів програми',
    ];

    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32);
            $table->string('platform', 8);
            $table->string('arch', 8);
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->unique(['version', 'platform', 'arch']);
            $table->index(['is_active', 'platform', 'arch']);
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
        Schema::dropIfExists('releases');
        DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->delete();
    }
};
