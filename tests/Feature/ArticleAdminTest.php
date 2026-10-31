<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_section_lists_only_articles_and_pages_list_hides_them(): void
    {
        Category::create(['name' => 'Інструкції']);
        Page::factory()->create(['title' => 'Стаття про кошик', 'slug' => 'stattia', 'category' => 'Інструкції']);
        Page::factory()->create(['title' => 'Звичайна сторінка', 'slug' => 'zvychayna', 'category' => null]);
        $this->actingAs(User::factory()->admin()->create(), 'backpack');

        $articles = $this->postJson('/admin/article/search')->assertOk()->json('data');
        $pages = $this->postJson('/admin/page/search')->assertOk()->json('data');

        $this->assertStringContainsString('Стаття про кошик', json_encode($articles, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('Звичайна сторінка', json_encode($articles, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('Звичайна сторінка', json_encode($pages, JSON_UNESCAPED_UNICODE));
        $this->assertStringNotContainsString('Стаття про кошик', json_encode($pages, JSON_UNESCAPED_UNICODE));
    }

    public function test_article_requires_category_and_is_created_in_blog(): void
    {
        Category::create(['name' => 'Інструкції']);
        $this->actingAs(User::factory()->admin()->create(), 'backpack');
        $payload = ['title' => 'Нова стаття', 'slug' => '', 'content' => '<p>Текст</p>', 'is_published' => 1];

        $this->post('/admin/article', $payload)->assertSessionHasErrors('category');
        $this->post('/admin/article', $payload + ['category' => 'Інструкції'])->assertSessionHasNoErrors();

        $article = Page::where('title', 'Нова стаття')->firstOrFail();
        $this->assertTrue($article->isArticle());
        $this->assertStringStartsWith('/blog/', parse_url($article->url(), PHP_URL_PATH));
    }

    public function test_article_shows_author_byline_and_person_schema(): void
    {
        $this->withoutVite();
        Category::create(['name' => 'Інструкції']);
        Page::factory()->create(['slug' => 'with-author', 'category' => 'Інструкції']);

        $this->get('/blog/with-author')->assertOk()
            ->assertSee('Автор:')
            ->assertSee('rel="author"', false)
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"author":{"@id":"'.url('/').'#author"}', false);
    }
}
