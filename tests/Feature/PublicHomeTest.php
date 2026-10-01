<?php

namespace Tests\Feature;

use App\Enums\PageRobotsType;
use App\Models\Package;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_route_checks_application_dependencies(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_home_page_lists_only_active_packages(): void
    {
        $this->withoutVite();
        Package::factory()->create(['name' => 'Видимий', 'is_active' => true]);
        Package::factory()->create(['name' => 'Прихований', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Видимий')
            ->assertDontSee('Прихований');
    }

    public function test_order_button_links_to_contact_url_or_falls_back_to_the_form(): void
    {
        $this->withoutVite();
        Package::factory()->create();

        config(['license.contact_url' => null]);
        $this->get('/')->assertSee('Замовити')->assertSee('href="#contact"', false);

        config(['license.contact_url' => 'https://t.me/example']);
        $this->get('/')->assertSee('Замовити')->assertSee('href="https://t.me/example"', false);
    }

    public function test_pricing_has_no_accounts_note(): void
    {
        $this->withoutVite();
        Package::factory()->create();

        $this->get('/')->assertDontSee('class="note"', false);
    }

    public function test_home_page_has_seo_markup(): void
    {
        $this->withoutVite();
        Package::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('<h1', false);
    }

    public function test_sitemap_and_robots_are_served(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(route('home'), false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_admin_is_not_indexable(): void
    {
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_pages_without_own_cover_share_the_default_large_social_image(): void
    {
        $this->withoutVite();
        Page::where('slug', 'pro-nas')->update(['og_image' => null]);

        $this->get('/pro-nas')->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('images/og-default.jpg').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $this->assertFileExists(public_path('images/og-default.jpg'));
    }

    public function test_brand_is_appended_to_seo_title_only_when_missing_and_it_fits(): void
    {
        $brand = SiteSetting::current()->header_logo;
        $page = new Page(['title' => 'Про нас']);
        $this->assertSame('Про нас | '.$brand, $page->seoTitle());

        $page->meta_title = 'Про '.$brand.': команда';
        $this->assertSame('Про '.$brand.': команда', $page->seoTitle());

        $page->meta_title = str_repeat('а', 58);
        $this->assertSame(str_repeat('а', 58), $page->seoTitle());
    }

    public function test_llms_files_list_published_pages_and_full_text(): void
    {
        $page = Page::factory()->create(['slug' => 'llm-visible', 'title' => 'Видима стаття', 'content' => '<h2>Розділ</h2><p>Текст <a href="/pro-nas">про нас</a></p>']);
        Page::factory()->create(['slug' => 'llm-hidden', 'title' => 'Прихована стаття', 'robots' => PageRobotsType::NoindexNofollow]);

        $this->get('/llms.txt')->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('# Numis')->assertSee('['.$page->title.']('.$page->url().')', false)->assertDontSee('Прихована стаття');
        $this->get('/llms-full.txt')->assertOk()->assertSee('#### Розділ')->assertSee('[про нас]('.url('/pro-nas').')', false)
            ->assertDontSee('<p>', false);
    }

    public function test_robots_txt_allows_ai_crawlers_and_lists_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('User-agent: GPTBot')->assertSee('User-agent: ClaudeBot')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_uppercase_page_url_redirects_permanently_to_the_lowercase_one(): void
    {
        $this->get('/Pro-Nas')->assertStatus(301)->assertRedirect(url('/pro-nas'));
    }

    public function test_sitemap_has_lastmod_for_home_page(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>'.route('home').'</loc>', false)->assertSee('<lastmod>', false);
    }

    public function test_article_pages_expose_article_metadata(): void
    {
        $this->withoutVite();
        $page = Page::factory()->create(['slug' => 'seo-article', 'category' => 'Купівля']);

        $this->get('/seo-article')->assertOk()->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('article:published_time', false)->assertSee('"@type":"Article"', false);
        $this->assertSame('Article', collect(json_decode(
            preg_match('~ld\+json">(.*?)</script>~s', $this->get('/'.$page->slug)->getContent(), $m) ? $m[1] : '{}', true
        )['@graph'])->firstWhere('@type', 'Article')['@type']);
    }
}
