<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Меню шапки й підвалу – дерево пунктів (як ApiMenuItem в iptel-ua: parent_id/lft/rgt/depth, впорядкування перетягуванням).
 * Старі списки посилань у налаштуваннях і прапорці «у шапці/підвалі» на сторінках переносяться в пункти меню.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu', 20);
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->unsignedInteger('lft')->nullable();
            $table->unsignedInteger('rgt')->nullable();
            $table->unsignedInteger('depth')->default(0);
            $table->string('title')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->boolean('open_in_new_tab')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['menu', 'lft']);
        });

        $site = DB::table('site_settings')->first();

        foreach (['header' => 'show_in_header', 'footer' => 'show_in_footer'] as $menu => $pageFlag) {
            $position = 0;
            $insert = function (array $attributes) use ($menu, &$position): void {
                DB::table('menu_items')->insert($attributes + [
                    'menu' => $menu, 'lft' => 2 * $position + 1, 'rgt' => 2 * $position + 2, 'depth' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $position++;
            };

            foreach (json_decode($site?->{$menu.'_links'} ?? '[]', true) ?: [] as $link) {
                $insert(['title' => $link['label'], 'url' => $link['url']]);
            }

            foreach (DB::table('pages')->where($pageFlag, true)->orderBy('sort_order')->orderBy('id')->get() as $page) {
                $insert(['page_id' => $page->id]);
            }
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['show_in_header', 'show_in_footer', 'sort_order']);
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['header_links', 'footer_links']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Міграцію не можна відкотити.');
    }
};
