<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'community_view' => 'Перегляд тем і коментарів спільноти',
        'community_manage' => 'Модерація та редагування спільноти',
    ];

    public function up(): void
    {
        Schema::create('community_topics', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('author_name', 100);
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_staff')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_members_only')->default(true);
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('rating')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
            $table->index(['status', 'is_pinned', 'published_at']);
        });

        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('license_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 100);
            $table->text('body');
            $table->boolean('is_staff')->default(false);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['community_topic_id', 'status']);
        });

        $pagesView = DB::table('permissions')->where('name', 'pages_view')->value('id');
        $roleIds = $pagesView ? DB::table('role_has_permissions')->where('permission_id', $pagesView)->pluck('role_id') : collect();

        foreach (self::PERMISSIONS as $name => $title) {
            $id = DB::table('permissions')->insertGetId([
                'name' => $name, 'title' => $title, 'group' => 'Спільнота', 'guard_name' => 'web',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insert(['permission_id' => $id, 'role_id' => $roleId]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $position = (int) MenuItem::where('menu', MenuItem::HEADER)->max('rgt');

        if (! MenuItem::where('menu', MenuItem::HEADER)->where('url', '/spilnota')->exists()) {
            MenuItem::create([
                'menu' => MenuItem::HEADER, 'title' => 'Спільнота', 'url' => '/spilnota', 'depth' => 1,
                'is_active' => true, 'lft' => $position + 1, 'rgt' => $position + 2,
            ]);
        }
    }

    public function down(): void
    {
        MenuItem::where('url', '/spilnota')->delete();
        DB::table('permissions')->whereIn('name', array_keys(self::PERMISSIONS))->delete();
        Schema::dropIfExists('community_comments');
        Schema::dropIfExists('community_topics');
    }
};
