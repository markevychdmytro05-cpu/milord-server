<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Модулі прикріплюються прямо до сторінки й до підвалу (як у iptel-ua, де сторінка – це і є шаблон з модулями).
 * Окрема сутність Layout більше не потрібна: її звʼязки переносяться, таблиці й права видаляються.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
        });

        Schema::create('site_setting_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_setting_id')->constrained('site_settings')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('module_id')->constrained('modules')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
        });

        $this->copyModules('pages', 'layout_id', 'page_module', 'page_id');
        $this->copyModules('site_settings', 'footer_layout_id', 'site_setting_module', 'site_setting_id');

        Schema::table('pages', fn (Blueprint $table) => $table->dropConstrainedForeignId('layout_id'));
        Schema::table('site_settings', fn (Blueprint $table) => $table->dropConstrainedForeignId('footer_layout_id'));

        Schema::dropIfExists('layout_module');
        Schema::dropIfExists('layouts');

        DB::table('permissions')->whereIn('name', ['layouts_view', 'layouts_create', 'layouts_update', 'layouts_delete'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('Міграцію не можна відкотити.');
    }

    private function copyModules(string $owners, string $layoutColumn, string $pivot, string $ownerKey): void
    {
        foreach (DB::table($owners)->whereNotNull($layoutColumn)->get(['id', $layoutColumn]) as $owner) {
            foreach (DB::table('layout_module')->where('layout_id', $owner->{$layoutColumn})->orderBy('sort_order')->get() as $row) {
                DB::table($pivot)->insert([$ownerKey => $owner->id, 'module_id' => $row->module_id, 'sort_order' => $row->sort_order]);
            }
        }
    }
};
