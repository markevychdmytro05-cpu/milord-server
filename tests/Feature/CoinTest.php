<?php

namespace Tests\Feature;

use App\Enums\PageRobotsType;
use App\Models\Coin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CoinTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_coin_is_shown_with_seo_tags_and_facts(): void
    {
        $this->withoutVite();
        Coin::factory()->create([
            'slug' => 'pavutyna', 'title' => 'Павутина', 'meta_title' => 'Монета Павутина НБУ',
            'meta_description' => 'Опис монети для пошуку', 'mintage' => 12000, 'denomination' => '10 грн',
        ]);

        $this->get('/monety-nbu/pavutyna')->assertOk()
            ->assertSee('<title>Монета Павутина НБУ | Numis</title>', false)
            ->assertSee('Опис монети для пошуку')
            ->assertSee('rel="canonical" href="'.url('/monety-nbu/pavutyna').'"', false)
            ->assertSee('12 000 шт.')
            ->assertSee('BreadcrumbList', false);
    }

    public function test_draft_coin_is_not_available_and_not_listed(): void
    {
        $this->withoutVite();
        Coin::factory()->draft()->create(['slug' => 'secret', 'title' => 'Секретна']);

        $this->get('/monety-nbu/secret')->assertNotFound();
        $this->get('/monety-nbu')->assertOk()->assertDontSee('Секретна');
    }

    public function test_coins_list_is_paginated_with_page_canonical(): void
    {
        $this->withoutVite();
        Coin::factory()->count(13)->create();

        $this->get('/monety-nbu')->assertOk()->assertViewHas('coins', fn ($coins) => $coins->count() === 12 && $coins->total() === 13);
        $this->get('/monety-nbu?page=2')->assertOk()
            ->assertViewHas('coins', fn ($coins) => $coins->count() === 1)
            ->assertSee('rel="canonical" href="'.url('/monety-nbu?page=2').'"', false);
    }

    public function test_pagination_links_to_first_page_without_page_parameter(): void
    {
        $this->withoutVite();
        Coin::factory()->count(13)->create();

        $this->get('/monety-nbu?page=2')->assertOk()
            ->assertSee('href="'.url('/monety-nbu').'" rel="prev"', false)
            ->assertDontSee('page=1', false);
    }

    public function test_paginated_and_sorted_list_pages_have_unique_titles_and_sort_variant_is_noindex(): void
    {
        $this->withoutVite();
        Coin::factory()->count(13)->create();

        $this->get('/monety-nbu')->assertOk()->assertSee('index, follow, max-image-preview', false);
        $this->get('/monety-nbu?page=2')->assertOk()->assertSee('Монети НБУ: сторінка 2 з 2', false);
        $this->get('/monety-nbu?sort=desc')->assertOk()->assertSee('content="noindex, follow"', false)
            ->assertSee('rel="canonical" href="'.url('/monety-nbu').'"', false);
    }

    public function test_out_of_range_and_first_page_params_are_redirected(): void
    {
        Coin::factory()->count(13)->create();

        $this->get('/monety-nbu?page=5')->assertRedirect(route('coins.index', ['page' => 2]))->assertStatus(301);
        $this->get('/monety-nbu?page=1')->assertRedirect(route('coins.index'))->assertStatus(301);
        $this->get('/monety-nbu?page=abc')->assertRedirect(route('coins.index'))->assertStatus(301);
    }

    public function test_out_of_range_page_redirects_to_plain_list_when_single_page(): void
    {
        Coin::factory()->count(3)->create();

        $this->get('/monety-nbu?page=4')->assertRedirect(route('coins.index'))->assertStatus(301);
    }

    public function test_short_title_strips_prefix_and_quotes_without_breaking_cyrillic(): void
    {
        $this->assertSame('Архістратиг Михаїл', Coin::factory()->make(['title' => 'Інвестиційна монета НБУ «Архістратиг Михаїл»'])->shortTitle());
        $this->assertSame('Паляниця', Coin::factory()->make(['title' => 'Монета НБУ «Паляниця»'])->shortTitle());
        $this->assertSame('Архістратиг Михаїл (до 35-річчя НБУ)', Coin::factory()->make(['title' => 'Монета НБУ «Архістратиг Михаїл» (до 35-річчя НБУ)'])->shortTitle());
    }

    public function test_coins_are_sorted_by_release_date_with_undated_last_and_direction_toggle(): void
    {
        $this->withoutVite();
        Coin::factory()->create(['title' => 'Монета НБУ «Без дати»', 'release_year' => null, 'release_month' => null]);
        Coin::factory()->create(['title' => 'Монета НБУ «Груднева»', 'release_year' => 2026, 'release_month' => 12]);
        Coin::factory()->create(['title' => 'Монета НБУ «Січнева»', 'release_year' => 2026, 'release_month' => 1]);

        $asc = $this->get('/monety-nbu')->getContent();
        $this->assertLessThan(strpos($asc, 'Грудн'), strpos($asc, 'Січн'));
        $this->assertLessThan(strpos($asc, 'Без дати'), strpos($asc, 'Грудн'));
        $this->assertStringContainsString('Січень 2026', $asc);
        $this->assertStringContainsString('Дата уточнюється', $asc);

        $desc = $this->get('/monety-nbu?sort=desc')->getContent();
        $this->assertLessThan(strpos($desc, 'Січн'), strpos($desc, 'Грудн'));
        $this->assertLessThan(strpos($desc, 'Без дати'), strpos($desc, 'Січн'));
    }

    public function test_exact_sale_date_sets_release_month_and_year(): void
    {
        $coin = Coin::factory()->create(['release_year' => 2026, 'release_month' => 1, 'sale_starts_at' => '2026-10-15 10:00:00']);

        $this->assertSame(10, $coin->fresh()->release_month);
        $this->assertSame('15 жовтня 2026 року', $coin->fresh()->releaseLabel());
    }

    public function test_coin_without_own_description_is_noindex_and_left_out_of_sitemap_and_llms(): void
    {
        $this->withoutVite();
        Coin::factory()->create(['slug' => 'thin-coin', 'title' => 'Монета НБУ «Тонка»', 'content' => null]);
        Coin::factory()->create(['slug' => 'full-coin', 'title' => 'Монета НБУ «Повна»', 'content' => '<p>Опис</p>']);

        $this->get('/monety-nbu/thin-coin')->assertOk()->assertSee('content="noindex, follow"', false);
        $this->get('/monety-nbu/full-coin')->assertOk()->assertSee('content="index, follow, max-image-preview:large, max-snippet:-1"', false);
        $this->get('/sitemap.xml')->assertSee(url('/monety-nbu/full-coin'))->assertDontSee('thin-coin');
        $this->get('/llms.txt')->assertSee(url('/monety-nbu/full-coin'))->assertDontSee('thin-coin');
    }

    public function test_seo_title_and_description_are_generated_from_coin_facts_when_empty(): void
    {
        $coin = Coin::factory()->make([
            'title' => 'Монета НБУ «Зубр»', 'meta_title' => null, 'meta_description' => null,
            'denomination' => '5 грн', 'metal' => 'CuNiZn', 'mintage' => 40000, 'release_year' => 2026, 'release_month' => 4,
        ]);

        $this->assertStringContainsString('Монета НБУ «Зубр»: тираж і характеристики', $coin->seoTitle());
        $this->assertStringContainsString('номінал 5 грн, CuNiZn, тираж 40 000 шт., вихід: квітень 2026', $coin->seoDescription());
    }

    public function test_product_schema_appears_only_when_price_is_known(): void
    {
        $this->withoutVite();
        Coin::factory()->create(['slug' => 'no-price', 'price' => null]);
        Coin::factory()->upcoming()->create(['slug' => 'with-price', 'price' => 450, 'nbu_url' => 'https://coins.bank.gov.ua/x']);

        $this->get('/monety-nbu/no-price')->assertOk()->assertDontSee('"@type":"Product"', false);
        $this->get('/monety-nbu/with-price')->assertOk()
            ->assertSee('"@type":"Product"', false)->assertSee('"price":"450.00"', false)
            ->assertSee('"priceCurrency":"UAH"', false)->assertSee('https://schema.org/PreOrder', false)
            ->assertSee('"availabilityStarts"', false);
    }

    public function test_coin_page_shows_photo_credit_and_image_object_schema(): void
    {
        $this->withoutVite();
        Coin::factory()->create(['slug' => 'with-photo', 'title' => 'Монета НБУ «Фото»', 'image' => '/uploads/coins/with-photo.jpg', 'image_source' => 'https://bank.gov.ua/ua/news/all/x']);
        Coin::factory()->create(['slug' => 'no-photo', 'image' => null]);

        $this->get('/monety-nbu/with-photo')->assertOk()
            ->assertSee('src="/uploads/coins/with-photo.jpg"', false)->assertSee('Фото:')->assertSee('https://bank.gov.ua/ua/news/all/x', false)
            ->assertSee('"@type":"ImageObject"', false)->assertSee('"creditText":"Національний банк України"', false);
        $this->get('/monety-nbu/no-photo')->assertOk()->assertDontSee('Фото:')->assertDontSee('"@type":"ImageObject"', false);
    }

    public function test_import_images_command_downloads_photo_and_fills_thin_description_from_nbu_catalog(): void
    {
        $coin = Coin::factory()->create(['slug' => 'test-import-photo', 'title' => 'Монета НБУ «Ми сильні. Ми разом. Тестова область»', 'image' => null, 'content' => null]);

        Http::fake([
            'bank.gov.ua/sitemap/*' => Http::response('<urlset><url><loc>https://bank.gov.ua/ua/uah/obig-coin/10-grn-2026-test</loc></url></urlset>'),
            'bank.gov.ua/ua/uah/obig-coin/10-grn-2026-test' => Http::response('<div class="hide-show-currency" id="1"><div class="box mt1"><h3>Обігова пам&#039;ятна монета 10 гривень &quot;Ми сильні. Ми разом. Тестова область&quot;</h3></div>'
                .'<img src="/admin_uploads/coin/aaa.jpeg"><img src="/admin_uploads/coin/bbb.jpeg"><div class="box"><p>Дата введення в обіг — 24.02.2026<br>Діаметр, мм — 23,5<br>Тираж, шт. — 2 000 000</p></div></div>'),
            'bank.gov.ua/admin_uploads/coin/bbb.jpeg' => Http::response('fake-jpeg-bytes', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->artisan('coins:import-images')->assertSuccessful();

        $coin->refresh();
        $this->assertSame('/uploads/coins/test-import-photo.jpg', $coin->image);
        $this->assertSame('https://bank.gov.ua/ua/uah/obig-coin/10-grn-2026-test', $coin->image_source);
        $this->assertFileExists(public_path('uploads/coins/test-import-photo.jpg'));
        $this->assertStringContainsString('Діаметр, мм: 23,5', $coin->content);
        $this->assertStringNotContainsString('Тираж', $coin->content);
        $this->assertFalse($coin->isThin());

        File::delete(public_path('uploads/coins/test-import-photo.jpg'));
    }

    public function test_import_images_command_takes_free_licensed_photo_from_commons_for_released_coins_only(): void
    {
        $released = Coin::factory()->create(['slug' => 'commons-released', 'title' => 'Монета НБУ «Тестова Унікальність»', 'image' => null, 'release_year' => now()->year, 'release_month' => 1]);
        $future = Coin::factory()->create(['slug' => 'commons-future', 'title' => 'Монета НБУ «Майбутня Унікальність»', 'image' => null, 'release_year' => now()->year + 1, 'release_month' => 1]);

        $file = fn (string $title, string $license, string $timestamp) => [
            'title' => 'File:'.$title, 'imageinfo' => [[
                'url' => 'https://upload.wikimedia.org/x/'.md5($title).'.png', 'descriptionurl' => 'https://commons.wikimedia.org/wiki/File:'.rawurlencode($title),
                'width' => 800, 'mime' => 'image/png', 'timestamp' => $timestamp,
                'extmetadata' => ['LicenseShortName' => ['value' => $license], 'Artist' => ['value' => '<a href="#">Національний банк України</a>']],
            ]],
        ];

        Http::fake([
            'commons.wikimedia.org/*' => Http::response(['query' => ['pages' => [
                1 => $file('Тестова Унікальність реверс.png', 'CC BY-NC 4.0', now()->year.'-05-01T10:00:00Z'),
                2 => $file('Тестова Унікальність аверс.png', 'Public domain', now()->year.'-05-01T10:00:00Z'),
                3 => $file('Тестова Унікальність реверс 2019.png', 'Public domain', now()->year.'-05-01T10:00:00Z'),
                4 => $file('Тестова Унікальність реверс.png', 'CC BY-SA 4.0', now()->year.'-05-01T10:00:00Z'),
            ]]]),
            'bank.gov.ua/sitemap/*' => Http::response('<urlset></urlset>'),
            'upload.wikimedia.org/*' => Http::response('bytes', 200, ['Content-Type' => 'image/png']),
            '*' => Http::response('', 404),
        ]);

        $this->artisan('coins:import-images')->assertSuccessful();

        $released->refresh();
        $this->assertSame('/uploads/coins/commons-released.png', $released->image);
        $this->assertSame('Wikimedia Commons, Національний банк України (CC BY-SA 4.0)', $released->image_credit);
        $this->assertStringContainsString('commons.wikimedia.org/wiki/File:', $released->image_source);
        $this->assertNull($future->fresh()->image);

        File::delete(public_path('uploads/coins/commons-released.png'));
    }

    public function test_photo_credit_shows_custom_author_and_license(): void
    {
        $this->withoutVite();
        Coin::factory()->create(['slug' => 'credited', 'image' => '/uploads/coins/x.jpg', 'image_source' => 'https://commons.wikimedia.org/wiki/File:X.png', 'image_credit' => 'Wikimedia Commons, Автор (CC BY-SA 4.0)']);

        $this->get('/monety-nbu/credited')->assertOk()->assertSee('Wikimedia Commons, Автор (CC BY-SA 4.0)')
            ->assertSee('"creditText":"Wikimedia Commons, Автор (CC BY-SA 4.0)"', false);
    }

    public function test_upcoming_coin_shows_sale_start(): void
    {
        $this->withoutVite();
        Coin::factory()->upcoming()->create(['slug' => 'soon']);

        $this->get('/monety-nbu/soon')->assertOk()->assertSee('Продаж починається');
    }

    public function test_uppercase_slug_redirects_to_canonical(): void
    {
        Coin::factory()->create(['slug' => 'zubr']);

        $this->get('/monety-nbu/ZUBR')->assertRedirect(route('coins.show', 'zubr'))->assertStatus(301);
    }

    public function test_sitemap_and_llms_list_only_indexable_published_coins(): void
    {
        Coin::factory()->create(['slug' => 'visible']);
        Coin::factory()->create(['slug' => 'hidden-coin', 'robots' => PageRobotsType::NoindexNofollow]);
        Coin::factory()->draft()->create(['slug' => 'draft-coin']);

        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/monety-nbu/visible'))->assertSee(url('/monety-nbu'))
            ->assertDontSee('hidden-coin')->assertDontSee('draft-coin');
        $this->get('/llms.txt')->assertOk()->assertSee(url('/monety-nbu/visible'))->assertDontSee('hidden-coin');
    }

    public function test_admin_creates_coin_and_slug_is_generated_from_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/coin', [
            'title' => 'Кирило Кожум’яка', 'slug' => '', 'is_published' => 1, 'sale_starts_at' => '2026-10-01 10:00:00',
        ])->assertSessionHasNoErrors();

        $coin = Coin::firstOrFail();
        $this->assertSame('kyrylo-kozumiaka', $coin->slug);
        $this->assertTrue($coin->is_published);
    }

    public function test_admin_rejects_invalid_nbu_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/coin', ['title' => 'Монета', 'nbu_url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('nbu_url');
    }

    public function test_admin_coin_list_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $coin = Coin::factory()->create(['title' => 'Монета НБУ «Адмінка»']);

        $this->actingAs($admin, 'backpack')->get('/admin/coin')->assertOk();
        $this->actingAs($admin, 'backpack')->get('/admin/coin/create')->assertOk()->assertSee('Місяць випуску');
        $this->actingAs($admin, 'backpack')->get("/admin/coin/{$coin->id}/edit")->assertOk()->assertSee('Монета НБУ «Адмінка»');
        $this->actingAs($admin, 'backpack')->postJson('/admin/coin/search')->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_user_without_permission_cannot_open_coin_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'backpack')->get('/admin/coin')->assertForbidden();
    }

    public function test_coin_publication_can_be_toggled_from_list(): void
    {
        $coin = Coin::factory()->create(['is_published' => true]);

        $this->actingAs(User::factory()->admin()->create(), 'backpack')
            ->postJson("/admin/coin/{$coin->id}/toggle")
            ->assertOk()->assertJson(['value' => false]);
    }

    public function test_import_command_creates_drafts_and_keeps_manual_edits_on_rerun(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'coins');
        file_put_contents($file, json_encode([
            ['title' => 'Монета НБУ «Зубр»', 'denomination' => '5 грн', 'mintage' => 40000, 'release_year' => 2026, 'excerpt' => 'План'],
            ['title' => 'Монета НБУ «Писанка»', 'denomination' => '10 грн', 'mintage' => 15000, 'release_year' => 2026],
        ], JSON_UNESCAPED_UNICODE));

        $this->artisan('coins:import', ['file' => $file])->assertSuccessful();

        $this->assertSame(2, Coin::count());
        $this->assertFalse(Coin::firstWhere('title', 'Монета НБУ «Зубр»')->is_published);

        Coin::firstWhere('title', 'Монета НБУ «Зубр»')->update(['price' => 450, 'is_published' => true, 'excerpt' => 'Моє']);
        $this->artisan('coins:import', ['file' => $file])->assertSuccessful();

        $coin = Coin::firstWhere('title', 'Монета НБУ «Зубр»');
        $this->assertSame(2, Coin::count());
        $this->assertTrue($coin->is_published);
        $this->assertSame('Моє', $coin->excerpt);
        $this->assertSame('450.00', $coin->price);

        unlink($file);
    }

    public function test_import_command_fails_on_invalid_file(): void
    {
        $this->artisan('coins:import', ['file' => '/nonexistent.json'])->assertFailed();
    }
}
