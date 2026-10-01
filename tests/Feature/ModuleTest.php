<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\ModuleTemplate;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ModuleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $setting
     */
    private function module(string $code, array $setting, string $name = 'Тестовий модуль'): Module
    {
        return ModuleTemplate::byCode($code)->makeModule($name, ['active' => 1] + $setting);
    }

    private function cta(string $title = 'Заклик', string $name = 'CTA'): Module
    {
        return $this->module('cta-row', ['url' => '/#pricing', 'uk' => ['title' => $title, 'text' => 'Опис', 'button_text' => 'Натисни']], $name);
    }

    public function test_module_templates_are_installed_by_migrations(): void
    {
        foreach (['text-section', 'benefit', 'faqs', 'cta-row', 'clients'] as $code) {
            $this->assertNotNull(ModuleTemplate::byCode($code), $code);
        }
    }

    public function test_module_is_drawn_by_class_and_view_of_its_template(): void
    {
        $html = $this->cta('Купуйте зараз')->draw();

        $this->assertStringContainsString('class="cta-row', $html);
        $this->assertStringContainsString('Купуйте зараз', $html);
        $this->assertStringContainsString('href="/#pricing"', $html);
    }

    public function test_inactive_module_draws_nothing(): void
    {
        $module = $this->cta();
        $module->setting = [...$module->setting->toArray(), 'active' => 0];

        $this->assertSame('', $module->draw());
    }

    public function test_faq_escapes_content_and_outputs_valid_json_ld(): void
    {
        $module = $this->module('faqs', ['items' => [['question' => 'Що <b>це</b>?', 'description' => 'Так"; </script><script>alert(1)']]]);

        $html = $module->draw();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('accordion__item', $html);
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $html, $match);
        $this->assertSame('FAQPage', json_decode($match[1], true)['@type']);
    }

    public function test_faq_without_items_draws_nothing(): void
    {
        $this->assertSame('', $this->module('faqs', ['items' => []])->draw());
    }

    public function test_page_keeps_modules_in_the_given_order(): void
    {
        [$first, $second] = [$this->cta('ПЕРШИЙ', 'A'), $this->cta('ДРУГИЙ', 'B')];

        $page = Page::factory()->create(['page_modules' => [$second->id, $first->id]]);
        $html = $page->fresh()->drawModules();

        $this->assertSame([$second->id, $first->id], $page->fresh()->page_modules);
        $this->assertLessThan(strpos($html, 'ПЕРШИЙ'), strpos($html, 'ДРУГИЙ'));

        $page->update(['page_modules' => [$first->id]]);
        $this->assertSame([$first->id], $page->fresh()->page_modules);
    }

    public function test_page_and_footer_render_their_modules(): void
    {
        $this->withoutVite();
        Page::factory()->create(['slug' => 'about-test', 'page_modules' => [$this->cta('МОДУЛЬ СТОРІНКИ')->id]]);
        SiteSetting::current()->update(['footer_modules' => [$this->cta('МОДУЛЬ ПІДВАЛУ', 'F')->id]]);

        $this->get('/about-test')->assertOk()->assertSee('МОДУЛЬ СТОРІНКИ')->assertSee('МОДУЛЬ ПІДВАЛУ');
    }

    public function test_admin_lists_modules_grouped_by_template(): void
    {
        $admin = User::factory()->admin()->create();
        $this->cta(name: 'Мій CTA');

        $this->actingAs($admin, 'backpack')->get('/admin/module')->assertOk()->assertSee('CTA row')->assertSee('Мій CTA');
    }

    public function test_admin_creates_module_from_template_form(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ModuleTemplate::byCode('cta-row');

        $this->actingAs($admin, 'backpack')->get("/admin/module/create/{$template->id}")->assertOk()->assertSee('Button URL');
        $this->actingAs($admin, 'backpack')->post("/admin/module/{$template->id}", [
            'name' => 'Новий CTA', 'active' => 1, 'bg_gray' => 1, 'url' => '/#pricing',
            'uk' => ['title' => 'Заголовок', 'text' => 'Текст', 'button_text' => 'Кнопка'],
        ])->assertRedirect();

        $module = Module::where('name', 'Новий CTA')->firstOrFail();
        $this->assertSame('Заголовок', $module->setting['uk']['title']);
        $this->assertSame(1, $module->setting['active']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function templateCodes(): array
    {
        return array_combine(
            ['text-section', 'benefit', 'faqs', 'cta-row', 'clients'],
            array_map(fn (string $code): array => [$code], ['text-section', 'benefit', 'faqs', 'cta-row', 'clients'])
        );
    }

    #[DataProvider('templateCodes')]
    public function test_create_form_renders_for_every_template(string $code): void
    {
        $admin = User::factory()->admin()->create();
        $template = ModuleTemplate::byCode($code);

        $this->actingAs($admin, 'backpack')->get("/admin/module/create/{$template->id}")->assertOk()->assertSee($template->fields()[0]['label']);
    }

    #[DataProvider('templateCodes')]
    public function test_edit_form_renders_for_every_template(string $code): void
    {
        $admin = User::factory()->admin()->create();
        $module = ModuleTemplate::byCode($code)->makeModule('M', ['active' => 1]);

        $this->actingAs($admin, 'backpack')->get("/admin/module/{$module->id}/edit")->assertOk();
    }

    public function test_template_rules_are_enforced(): void
    {
        $admin = User::factory()->admin()->create();
        $template = ModuleTemplate::byCode('cta-row');

        $this->actingAs($admin, 'backpack')->post("/admin/module/{$template->id}", [
            'name' => 'X', 'active' => 1, 'url' => 'javascript:alert(1)', 'uk' => ['title' => '', 'button_text' => ''],
        ])->assertSessionHasErrors(['url', 'uk.title', 'uk.button_text']);
    }

    public function test_edit_form_is_filled_and_update_removes_deleted_list_items(): void
    {
        $admin = User::factory()->admin()->create();
        $module = $this->module('faqs', ['items' => [
            ['question' => 'Питання 1', 'description' => 'Відповідь 1'],
            ['question' => 'Питання 2', 'description' => 'Відповідь 2'],
        ]]);

        $this->actingAs($admin, 'backpack')->get("/admin/module/{$module->id}/edit")->assertOk()->assertSee('Питання 2');
        $this->actingAs($admin, 'backpack')->put("/admin/module/{$module->id}", [
            'name' => 'FAQ оновлено', 'active' => 1,
            'items' => [['question' => 'Лишилось', 'description' => 'Відповідь']],
        ])->assertRedirect();

        $module->refresh();
        $this->assertSame('FAQ оновлено', $module->name);
        $this->assertCount(1, $module->setting['items']);
        $this->assertSame('Лишилось', $module->setting['items'][0]['question']);
    }

    public function test_admin_can_copy_preview_and_delete_module(): void
    {
        $this->withoutVite();
        $admin = User::factory()->admin()->create();
        $module = $this->cta('Прев\'ю заклик');

        $this->actingAs($admin, 'backpack')->get("/admin/module/{$module->id}/preview")->assertOk()->assertSee('Прев&#039;ю заклик', false);
        $before = Module::count();
        $this->actingAs($admin, 'backpack')->post("/admin/module/{$module->id}/copy")->assertRedirect();
        $this->assertSame($before + 1, Module::count());
        $this->assertDatabaseHas('modules', ['name' => 'CTA (копія)']);

        $this->actingAs($admin, 'backpack')->delete("/admin/module/{$module->id}")->assertOk();
        $this->assertSame($before, Module::count());
    }

    public function test_admin_saves_page_modules_in_submitted_order(): void
    {
        $admin = User::factory()->admin()->create();
        [$a, $b] = [$this->cta('A', 'A'), $this->cta('B', 'B')];

        $this->actingAs($admin, 'backpack')->post('/admin/page', [
            'type' => 'page', 'title' => 'Лендінг', 'slug' => '', 'is_published' => 1,
            'page_modules' => [$b->id, $a->id],
        ])->assertRedirect();

        $this->assertSame([$b->id, $a->id], Page::where('title', 'Лендінг')->firstOrFail()->page_modules);
    }

    public function test_page_form_has_slug_generator_and_ordered_module_list(): void
    {
        $admin = User::factory()->admin()->create();
        $this->cta(name: 'Доступний модуль');

        $this->actingAs($admin, 'backpack')->get('/admin/page/create')->assertOk()
            ->assertSee('Згенерувати')->assertSee('Доступний модуль');
    }

    public function test_manager_cannot_open_modules_admin(): void
    {
        $manager = User::factory()->create();

        $this->actingAs($manager, 'backpack')->get('/admin/module')->assertForbidden();
    }

    public function test_make_module_template_command_generates_class_migration_and_view(): void
    {
        $this->artisan('make:module-template', ['code' => 'demo-banner'])->assertSuccessful();

        $class = app_path('Modules/DemoBannerModule.php');
        $view = resource_path('views/modules/demo-banner.blade.php');
        $migration = glob(database_path('migrations/*_add_demo-banner_module_template.php'));

        try {
            $this->assertFileExists($class);
            $this->assertFileExists($view);
            $this->assertCount(1, $migration);
            $this->assertStringContainsString('class DemoBannerModule implements ModuleInterface', File::get($class));
            $this->assertStringContainsString('modules.demo-banner', File::get($class));
            $this->assertStringContainsString("'code' => 'demo-banner'", File::get($migration[0]));
        } finally {
            File::delete([$class, $view, ...$migration]);
        }
    }
}
