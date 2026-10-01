<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'leads_view' => 'Перегляд заявок із форми зв’язку',
        'leads_update' => 'Позначення заявок обробленими',
        'leads_delete' => 'Видалення заявок',
    ];

    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact');
            $table->text('message')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('ip', 45)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamps();
            $table->index('created_at');
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('telegram')->nullable()->after('footer_note');
            $table->string('whatsapp')->nullable()->after('telegram');
            $table->string('viber')->nullable()->after('whatsapp');
        });

        $pagesView = DB::table('permissions')->where('name', 'pages_view')->value('id');
        $roleIds = $pagesView ? DB::table('role_has_permissions')->where('permission_id', $pagesView)->pluck('role_id') : collect();

        foreach (self::PERMISSIONS as $name => $title) {
            $id = DB::table('permissions')->insertGetId([
                'name' => $name, 'title' => $title, 'group' => 'Заявки', 'guard_name' => 'web',
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

        Schema::table('site_settings', fn (Blueprint $table) => $table->dropColumn(['telegram', 'whatsapp', 'viber']));
        Schema::dropIfExists('leads');
    }
};
