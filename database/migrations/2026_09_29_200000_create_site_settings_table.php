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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('header_logo');
            $table->json('header_links')->nullable();
            $table->boolean('header_show_status')->default(true);
            $table->boolean('header_show_clock')->default(true);
            $table->string('footer_text');
            $table->string('footer_note')->nullable();
            $table->json('footer_links')->nullable();
            $table->timestamps();
        });

        DB::table('site_settings')->insert([
            'header_logo' => 'Numis',
            'header_links' => json_encode([
                ['label' => 'Можливості', 'url' => '/#features'],
                ['label' => 'Як це працює', 'url' => '/#how'],
                ['label' => 'Тарифи', 'url' => '/#pricing'],
            ], JSON_UNESCAPED_UNICODE),
            'footer_text' => '© Numis',
            'footer_note' => 'Europe/Kyiv',
            'footer_links' => json_encode([], JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'site_manage', 'title' => 'Керування шапкою й підвалом сайту', 'group' => 'Адміністрування',
            'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $accessManage = DB::table('permissions')->where('name', 'access_manage')->value('id');
        $roleIds = $accessManage ? DB::table('role_has_permissions')->where('permission_id', $accessManage)->pluck('role_id') : collect();

        foreach ($roleIds as $roleId) {
            DB::table('role_has_permissions')->insert(['permission_id' => $permissionId, 'role_id' => $roleId]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('name', 'site_manage')->delete();
        Schema::dropIfExists('site_settings');
    }
};
