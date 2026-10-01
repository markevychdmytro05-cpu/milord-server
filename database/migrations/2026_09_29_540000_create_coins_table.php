<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'coins_view' => 'Перегляд монет',
        'coins_create' => 'Створення монет',
        'coins_update' => 'Редагування монет',
        'coins_delete' => 'Видалення монет',
    ];

    public function up(): void
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('denomination', 50)->nullable();
            $table->string('series')->nullable();
            $table->string('metal', 100)->nullable();
            $table->unsignedSmallInteger('release_year')->nullable();
            $table->unsignedInteger('mintage')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->dateTime('sale_starts_at')->nullable();
            $table->string('nbu_url', 2048)->nullable();
            $table->string('image')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('og_image')->nullable();
            $table->string('robots', 30)->default('index, follow');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'sale_starts_at']);
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
        Schema::dropIfExists('coins');
    }
};
