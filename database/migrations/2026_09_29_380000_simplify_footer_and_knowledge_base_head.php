<?php

use App\Models\MenuItem;
use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Як у Civic Linker: у футері лише загальні сторінки й документи, а хаб бази знань має власний заголовок у ряд із фільтрами.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('show_title')->default(true)->after('excerpt');
        });

        $this->extendArticlesTemplate();
        $this->configureKnowledgeBase();
        $this->rebuildFooterMenu();
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('show_title');
        });
    }

    private function extendArticlesTemplate(): void
    {
        $template = ModuleTemplate::byCode('articles');

        if (! $template) {
            return;
        }

        $config = $template->template->toArray();
        $config['fields']['heading_tag'] = ['label' => 'Тег заголовка', 'input' => 'select', 'provider' => ['h2' => 'H2', 'h1' => 'H1 (якщо це головний заголовок сторінки)']];
        $config['rules']['heading_tag'] = 'nullable|string|in:h1,h2';
        $template->update(['template' => $config]);
    }

    private function configureKnowledgeBase(): void
    {
        $hub = Page::where('slug', 'help')->first();
        $module = Module::where('name', 'База знань – Статті')->first();

        if ($hub) {
            $hub->update(['show_title' => false, 'content' => null]);
        }

        if ($module) {
            $setting = $module->setting->toArray();
            $setting['heading_tag'] = 'h1';
            $setting['uk']['title'] = 'База знань';
            $module->update(['setting' => $setting]);
        }
    }

    private function rebuildFooterMenu(): void
    {
        $about = Page::where('slug', 'about')->first();
        $hub = Page::where('slug', 'help')->first();
        $privacy = Page::where('slug', 'privacy')->first();
        $terms = Page::where('slug', 'terms')->first();

        MenuItem::where('menu', MenuItem::FOOTER)->delete();

        $position = 0;
        $create = function (array $attributes, ?int $parentId = null, int $depth = 1) use (&$position): MenuItem {
            return MenuItem::create($attributes + [
                'menu' => MenuItem::FOOTER, 'parent_id' => $parentId, 'depth' => $depth, 'is_active' => true,
                'lft' => ++$position, 'rgt' => ++$position,
            ]);
        };

        $create(['title' => 'Можливості', 'url' => '/#features']);
        $create(['title' => 'Тарифи', 'url' => '/#pricing']);
        $hub && $create(['page_id' => $hub->id, 'title' => 'База знань']);
        $about && $create(['page_id' => $about->id]);
        $create(['title' => 'Контакти', 'url' => '/#contact']);

        $documents = array_filter([$privacy, $terms]);

        if ($documents) {
            $root = MenuItem::create(['menu' => MenuItem::FOOTER, 'title' => 'Документи', 'depth' => 1, 'is_active' => true, 'lft' => ++$position, 'rgt' => $position]);

            foreach ($documents as $document) {
                $create(['page_id' => $document->id], $root->id, 2);
            }

            $root->update(['rgt' => ++$position]);
        }
    }
};
