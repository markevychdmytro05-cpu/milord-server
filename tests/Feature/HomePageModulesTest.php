<?php

namespace Tests\Feature;

use App\Models\Coin;
use App\Models\Module;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_seeded_with_modules(): void
    {
        $home = Page::where('slug', Page::ROOT_SLUG)->firstOrFail();

        $this->assertTrue($home->is_published);
        $this->assertCount(6, $home->page_modules);
    }

    public function test_home_shows_upcoming_coins_block_and_hides_it_without_coins(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()->assertDontSee('id="coins"', false);

        Coin::factory()->create(['title' => 'Монета НБУ «Майбутня»', 'release_year' => now()->year, 'release_month' => now()->month]);
        Coin::factory()->create(['title' => 'Монета НБУ «Минула»', 'release_year' => now()->subYear()->year, 'release_month' => 1]);
        Coin::factory()->draft()->create(['title' => 'Монета НБУ «Чернетка»', 'release_year' => now()->year, 'release_month' => now()->month]);

        $this->get('/')->assertOk()->assertSee('id="coins"', false)->assertSee('Майбутня')
            ->assertDontSee('Минула')->assertDontSee('Чернетка')->assertSee(route('coins.index'));
    }

    public function test_home_renders_only_its_active_modules_in_order(): void
    {
        $this->withoutVite();
        $home = Page::where('slug', Page::ROOT_SLUG)->firstOrFail();
        $heroId = $home->page_modules[0];

        $hero = Module::findOrFail($heroId);
        $setting = $hero->setting->toArray();
        $setting['uk']['title_1'] = 'Новий заголовок з адмінки';
        $hero->setting = $setting;
        $hero->save();

        $this->get('/')->assertOk()->assertSee('Новий заголовок з адмінки')->assertSee('id="pricing"', false);

        $setting['active'] = 0;
        $hero->setting = $setting;
        $hero->save();

        $this->get('/')->assertOk()->assertDontSee('Новий заголовок з адмінки');
    }

    public function test_root_slug_is_unique(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/page', ['title' => 'Друга головна', 'slug' => '/', 'is_published' => 1])
            ->assertSessionHasErrors('slug');
    }

    public function test_home_page_is_not_available_by_its_slug_or_in_sitemap_twice(): void
    {
        $this->withoutVite();

        $this->get('/home')->assertNotFound();
        $this->assertSame(1, substr_count($this->get('/sitemap.xml')->getContent(), '<loc>'.route('home').'</loc>'));
    }

    public function test_home_page_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $home = Page::where('slug', Page::ROOT_SLUG)->firstOrFail();

        $this->actingAs($admin, 'backpack')->delete("/admin/page/{$home->id}")->assertStatus(409);
        $this->assertDatabaseHas('pages', ['id' => $home->id]);
    }

    public function test_admin_edits_home_page_seo(): void
    {
        $admin = User::factory()->admin()->create();
        $home = Page::where('slug', Page::ROOT_SLUG)->firstOrFail();

        $this->actingAs($admin, 'backpack')->put("/admin/page/{$home->id}", [
            'title' => 'Головна', 'slug' => '/', 'is_published' => 1,
            'meta_title' => 'Новий SEO заголовок', 'meta_description' => 'Опис', 'page_modules' => $home->page_modules,
        ])->assertRedirect();

        $this->assertSame('Новий SEO заголовок', $home->fresh()->meta_title);
        $this->assertCount(6, $home->fresh()->page_modules);
    }

    public function test_feature_points_keep_cyrillic_text_intact(): void
    {
        $this->withoutVite();
        $features = Module::whereHas('module_template', fn ($query) => $query->where('code', 'features'))->firstOrFail();
        $setting = $features->setting->toArray();
        $setting['items'][0]['points'] = "Ключ у системному сховищі\nЖодних паролів на сервері";
        $features->setting = $setting;
        $features->save();

        $this->get('/')->assertOk()
            ->assertSee('<li>Ключ у системному сховищі</li>', false)
            ->assertSee('<li>Жодних паролів на сервері</li>', false);
    }
}
