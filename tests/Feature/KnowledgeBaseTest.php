<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_page_shows_article_cards_with_date_and_category(): void
    {
        $this->withoutVite();

        $this->get('/baza-znan-numis')->assertOk()
            ->assertSee('class="kb-grid"', false)->assertSee('class="kb-card"', false)
            ->assertSee('class="kb-tag kb-c-', false)->assertSee('kb-date', false)->assertSee('року');
    }

    public function test_filters_list_categories_and_narrow_the_grid(): void
    {
        $this->withoutVite();
        Page::factory()->create(['title' => 'Стаття про ліцензію Х', 'slug' => 'lic-x', 'category' => 'Ліцензія']);
        Page::factory()->create(['title' => 'Стаття про запуск Ю', 'slug' => 'run-y', 'category' => 'Запуск']);

        $this->get('/baza-znan-numis')->assertSee('class="kb-filters"', false)->assertSee('Стаття про ліцензію Х')->assertSee('Стаття про запуск Ю');
        $this->get('/baza-znan-numis?category='.urlencode('Запуск'))->assertSee('Стаття про запуск Ю')->assertDontSee('Стаття про ліцензію Х');
        $this->get('/baza-znan-numis?category=не-існує')->assertSee('Стаття про ліцензію Х');
    }

    public function test_first_page_and_out_of_range_params_are_redirected_and_category_is_kept(): void
    {
        $this->withoutVite();
        Page::query()->whereNotNull('category')->delete();
        Page::factory()->count(40)->create(['category' => 'Тест']);

        $this->get('/baza-znan-numis?page=1')->assertRedirect('/baza-znan-numis')->assertStatus(301);
        $this->get('/baza-znan-numis?page=abc')->assertRedirect('/baza-znan-numis')->assertStatus(301);
        $this->get('/baza-znan-numis?category='.urlencode('Тест').'&page=1')->assertRedirect('/baza-znan-numis?category=%D0%A2%D0%B5%D1%81%D1%82');

        $response = $this->get('/baza-znan-numis?page=99')->assertStatus(301);
        $this->assertMatchesRegularExpression('~/baza-znan-numis\?page=\d+$~', $response->headers->get('Location'));
        $this->followingRedirects()->get('/baza-znan-numis?page=99')->assertOk();
    }

    public function test_drafts_and_pages_without_category_are_not_listed(): void
    {
        $this->withoutVite();
        Page::factory()->draft()->create(['title' => 'Чернетка статті', 'slug' => 'draft-a', 'category' => 'Ліцензія']);
        Page::factory()->create(['title' => 'Звичайна сторінка без категорії', 'slug' => 'plain-p']);

        $this->get('/baza-znan-numis')->assertDontSee('Чернетка статті')->assertDontSee('Звичайна сторінка без категорії');
    }

    public function test_newest_article_comes_first_and_cover_image_is_used(): void
    {
        $this->withoutVite();
        Page::query()->whereNotNull('category')->delete();
        Page::factory()->create(['title' => 'Стара стаття АА', 'slug' => 'old-a', 'category' => 'Тест', 'published_at' => now()->subYear()]);
        Page::factory()->create(['title' => 'Нова стаття ББ', 'slug' => 'new-b', 'category' => 'Тест', 'published_at' => now()->addDay(), 'og_image' => '/uploads/cover.png']);

        $html = $this->get('/baza-znan-numis')->getContent();

        $this->assertLessThan(strpos($html, 'Стара стаття АА'), strpos($html, 'Нова стаття ББ'));
        $this->assertStringContainsString('src="/uploads/cover.png"', $html);
    }

    public function test_admin_can_set_category_and_date(): void
    {
        $admin = User::factory()->admin()->create();
        Category::create(['name' => 'Гайди']);

        $this->actingAs($admin, 'backpack')->post('/admin/page', [
            'title' => 'Нова стаття', 'slug' => '', 'is_published' => 1, 'category' => 'Гайди', 'published_at' => '2026-05-01 10:00:00',
        ])->assertRedirect();

        $page = Page::where('title', 'Нова стаття')->firstOrFail();
        $this->assertSame('Гайди', $page->category);
        $this->assertSame('2026-05-01', $page->published_at->toDateString());
    }

    public function test_help_hub_has_its_own_h1_and_no_duplicate_page_title(): void
    {
        $this->withoutVite();

        $html = $this->get('/baza-znan-numis')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('<h1 class="display">База знань</h1>', $html);
        $this->assertStringContainsString('page-article grid-bg is-compact', $html);
    }

    public function test_footer_links_only_general_pages_and_documents(): void
    {
        $this->withoutVite();

        $html = $this->get('/')->assertOk()->getContent();
        $footer = substr($html, strpos($html, '<footer'));

        $this->assertStringContainsString('href="'.url('/baza-znan-numis').'"', $footer);
        $this->assertStringContainsString('href="'.url('/polityka-konfidentsiynosti').'"', $footer);
        $this->assertStringNotContainsString('iak-pidkliuchyty-adspower-do-numis-pokrokova-instruktsiia', $footer);
        $this->assertStringNotContainsString('/services/', $footer);
    }

    public function test_category_colours_are_stable_and_shared(): void
    {
        $this->assertSame(Page::categoryColor('Ліцензія'), Page::categoryColor('Ліцензія'));
        $this->assertContains(Page::categoryColor('Купівля'), [0, 1, 2, 3, 4, 5]);
        $this->assertSame(0, Page::categoryColor(null));

        $colors = collect(['Купівля', 'Ліцензія', 'Налаштування'])->map(fn (string $category): int => Page::categoryColor($category));
        $this->assertSame(3, $colors->unique()->count());

        $this->withoutVite();
        $html = $this->get('/baza-znan-numis')->assertOk()->getContent();
        $this->assertStringContainsString('kb-c-'.Page::categoryColor('Ліцензія'), $html);
    }

    public function test_article_page_shows_read_also_with_same_category_first(): void
    {
        $this->withoutVite();
        Page::query()->where('slug', '!=', Page::ROOT_SLUG)->delete();
        $article = Page::factory()->create(['title' => 'Головна стаття', 'slug' => 'main-a', 'category' => 'Купівля', 'published_at' => now()->subDays(30)]);
        Page::factory()->create(['title' => 'Свіжа інша категорія', 'slug' => 'other-c', 'category' => 'Ліцензія', 'published_at' => now()]);
        Page::factory()->create(['title' => 'Стара з тієї ж категорії', 'slug' => 'same-c', 'category' => 'Купівля', 'published_at' => now()->subDays(60)]);

        $html = $this->get('/main-a')->assertOk()->assertSee('Читають також')->assertSee('class="read-also"', false)->getContent();

        $aside = substr($html, strpos($html, 'class="read-also"'));
        $this->assertLessThan(strpos($aside, 'Свіжа інша категорія'), strpos($aside, 'Стара з тієї ж категорії'));
        $this->assertCount(2, $article->relatedArticles(4));
    }

    public function test_pages_without_category_have_no_sidebar(): void
    {
        $this->withoutVite();
        Page::factory()->create(['slug' => 'plain-x']);

        $this->get('/plain-x')->assertOk()->assertDontSee('Читають також');
    }

    public function test_seeded_articles_have_cover_images_that_exist(): void
    {
        $this->withoutVite();

        foreach (Page::whereNotNull('category')->get() as $article) {
            $this->assertNotNull($article->og_image, $article->slug);
            $this->assertFileExists(public_path(ltrim($article->og_image, '/')));
        }

        $this->get('/baza-znan-numis')->assertOk()->assertSee('src="/images/kb/', false);
    }

    public function test_knowledge_base_is_paginated_and_keeps_the_category_filter(): void
    {
        $this->withoutVite();
        Page::query()->whereNotNull('category')->delete();
        foreach (range(1, 11) as $number) {
            Page::factory()->create(['title' => "Стаття №{$number}", 'slug' => "pg-{$number}", 'category' => 'Гайди', 'published_at' => now()->subDays($number)]);
        }

        $this->get('/baza-znan-numis')->assertOk()->assertSee('Стаття №1')->assertDontSee('Стаття №11')
            ->assertSee('kb-pagination', false)->assertSee('rel="next"', false);
        $this->get('/baza-znan-numis?page=2')->assertOk()->assertSee('Стаття №11')->assertDontSee('Стаття №1<', false)
            ->assertSee('<link rel="canonical" href="'.url('/baza-znan-numis').'?page=2">', false);
        $this->get('/baza-znan-numis?category='.urlencode('Гайди').'&page=2')->assertSee('category=%D0%93%D0%B0%D0%B9%D0%B4%D0%B8', false);
    }

    public function test_pagination_is_hidden_when_everything_fits_on_one_page(): void
    {
        $this->withoutVite();
        Page::query()->whereNotNull('category')->delete();
        Page::factory()->create(['slug' => 'only-one', 'category' => 'Гайди']);

        $this->get('/baza-znan-numis')->assertOk()->assertDontSee('kb-pagination', false);
    }
}
