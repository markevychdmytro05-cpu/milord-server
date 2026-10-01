<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_pages_render_faq_markup_once_and_a_call_to_action(): void
    {
        $this->withoutVite();

        foreach (['auto-buy' => '/services/avtomatychna-kupivlia-monet-nbu', 'pro-nas' => '/pro-nas', 'cherha-y-pomylka-429-na-sayti-nbu-shcho-robyty' => '/cherha-y-pomylka-429-na-sayti-nbu-shcho-robyty'] as $slug => $url) {
            $response = $this->get($url)->assertOk();

            $this->assertSame(1, substr_count($response->getContent(), '"@type":"FAQPage"'), $slug);
            $response->assertSee('Часті питання')->assertSee('Спробуйте Numis безкоштовно 3 дні');
        }
    }

    public function test_legal_pages_have_no_seo_modules(): void
    {
        $this->assertSame([], Page::where('slug', 'polityka-konfidentsiynosti')->firstOrFail()->page_modules);
        $this->assertSame([], Page::where('slug', 'publichna-oferta')->firstOrFail()->page_modules);
    }

    public function test_every_published_content_page_has_a_faq(): void
    {
        $withoutModules = Page::published()->whereNotIn('slug', ['/', 'polityka-konfidentsiynosti', 'publichna-oferta'])->get()
            ->filter(fn (Page $page): bool => $page->page_modules === []);

        $this->assertTrue($withoutModules->isEmpty(), $withoutModules->pluck('slug')->implode(', '));
    }
}
