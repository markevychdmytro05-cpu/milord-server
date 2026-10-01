<?php

namespace Tests\Feature;

use App\Enums\PageRobotsType;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'page', 'title' => 'Про нас', 'slug' => '', 'excerpt' => 'Коротко',
            'content' => '<p>Текст</p>', 'is_published' => 1,
        ];
    }

    public function test_published_page_is_shown_with_seo_tags(): void
    {
        $this->withoutVite();
        Page::factory()->create([
            'slug' => 'about-test', 'title' => 'Про нас', 'meta_title' => 'SEO про нас',
            'meta_description' => 'Опис для пошуку', 'robots' => PageRobotsType::NoindexNofollow,
        ]);

        $this->get('/about-test')->assertOk()
            ->assertSee('<title>SEO про нас | Numis</title>', false)
            ->assertSee('Опис для пошуку')
            ->assertSee('noindex, nofollow')
            ->assertSee('rel="canonical" href="'.url('/about-test').'"', false);
    }

    public function test_page_content_and_schema_do_not_execute_editor_html(): void
    {
        $this->withoutVite();
        Page::factory()->create([
            'slug' => 'safe-content',
            'title' => 'Safe </script><script>alert(1)</script>',
            'content' => '<p>Корисний <a href="/help" onclick="alert(1)">текст</a></p><script>alert(2)</script>',
        ]);

        $this->get('/safe-content')->assertOk()
            ->assertSee('<a href="/help">текст</a>', false)
            ->assertDontSee('onclick=', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('\\u003C/script\\u003E', false);
    }

    public function test_faq_section_is_exposed_as_faq_page_schema(): void
    {
        $this->withoutVite();
        Page::factory()->create(['slug' => 'with-faq', 'content' => '<p>Вступ</p><h2>Часті питання</h2><h3>Що це?</h3><p>Відповідь <a href="/x">один</a>.</p><h3>Як це працює?</h3><p>Відповідь два.</p><h2>Інше</h2><p>Текст</p>']);
        Page::factory()->create(['slug' => 'no-faq', 'content' => '<h2>Часті питання</h2><h3>Лише одне?</h3><p>Так.</p>']);

        $this->get('/with-faq')->assertOk()->assertSee('"@type":"FAQPage"', false)->assertSee('"name":"Що це?"', false)
            ->assertSee('"text":"Відповідь один."', false)->assertSee('"name":"Як це працює?"', false)->assertDontSee('"name":"Інше"', false);
        $this->get('/no-faq')->assertOk()->assertDontSee('"@type":"FAQPage"', false);
    }

    public function test_draft_is_not_available(): void
    {
        $this->withoutVite();
        Page::factory()->draft()->create(['slug' => 'secret']);

        $this->get('/secret')->assertNotFound();
    }

    public function test_service_lives_under_services_prefix(): void
    {
        $this->withoutVite();
        Page::factory()->service()->create(['slug' => 'seo', 'title' => 'SEO-просування']);

        $this->get('/services/seo')->assertOk()->assertSee('SEO-просування');
        $this->get('/seo')->assertNotFound();
    }

    public function test_sitemap_lists_only_indexable_published_pages(): void
    {
        Page::factory()->create(['slug' => 'ok']);
        Page::factory()->create(['slug' => 'hidden', 'robots' => PageRobotsType::NoindexNofollow]);
        Page::factory()->draft()->create(['slug' => 'draft']);

        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/ok'))->assertDontSee('hidden')->assertDontSee('draft');
    }

    public function test_admin_creates_page_and_slug_is_generated_from_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/page', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('pages', ['title' => 'Про нас', 'slug' => 'pro-nas-1', 'is_published' => true]);
    }

    public function test_reserved_slug_and_duplicates_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        Page::factory()->create(['slug' => 'taken']);

        $this->actingAs($admin, 'backpack')->post('/admin/page', $this->payload(['slug' => 'admin']))->assertSessionHasErrors('slug');
        $this->actingAs($admin, 'backpack')->post('/admin/page', $this->payload(['slug' => 'taken']))->assertSessionHasErrors('slug');
    }

    public function test_manager_without_permission_cannot_open_pages_admin(): void
    {
        $manager = User::factory()->create();

        $this->actingAs($manager, 'backpack')->get('/admin/page')->assertForbidden();
    }

    public function test_page_publication_can_be_toggled_from_list(): void
    {
        $page = Page::factory()->create(['is_published' => true]);

        $this->actingAs(User::factory()->admin()->create(), 'backpack')
            ->postJson("/admin/page/{$page->id}/toggle")
            ->assertOk()->assertJson(['value' => false]);

        $this->assertFalse($page->fresh()->is_published);

        $this->actingAs(User::factory()->create(), 'backpack')
            ->postJson("/admin/page/{$page->id}/toggle")
            ->assertForbidden();
    }

    public function test_empty_slug_is_generated_from_ukrainian_title_by_transliteration(): void
    {
        $page = Page::factory()->create(['slug' => '', 'title' => 'Як купити монету: шість кроків']);

        $this->assertSame('iak-kupyty-monetu-shist-krokiv', $page->fresh()->slug);
    }

    public function test_generated_slug_is_unique_within_type(): void
    {
        $first = Page::factory()->create(['slug' => '', 'title' => 'Про нас двох']);
        $second = Page::factory()->create(['slug' => '', 'title' => 'Про нас двох']);

        $this->assertNotSame($first->slug, $second->slug);
    }
}
