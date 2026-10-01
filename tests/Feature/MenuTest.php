<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function item(string $menu, array $attributes, int $position = 0): MenuItem
    {
        return MenuItem::create($attributes + [
            'menu' => $menu, 'lft' => 2 * $position + 1, 'rgt' => 2 * $position + 2, 'depth' => 1, 'is_active' => true,
        ]);
    }

    public function test_migrated_menu_keeps_default_header_links(): void
    {
        $this->assertGreaterThanOrEqual(3, MenuItem::where('menu', 'header')->count());
    }

    public function test_header_menu_shows_pages_and_urls_in_order_and_hides_unavailable_items(): void
    {
        $this->withoutVite();
        MenuItem::query()->delete();
        $page = Page::factory()->create(['title' => 'Про нас', 'slug' => 'about-test']);
        $draft = Page::factory()->draft()->create(['title' => 'Чернетка', 'slug' => 'draft']);

        $this->item('header', ['title' => 'Зовнішній', 'url' => 'https://example.com', 'open_in_new_tab' => true], 0);
        $this->item('header', ['page_id' => $page->id], 1);
        $this->item('header', ['page_id' => $draft->id], 2);
        $this->item('header', ['title' => 'Вимкнений', 'url' => '/x', 'is_active' => false], 3);

        $html = $this->get('/')->assertOk()->assertSee('Про нас')->assertDontSee('Чернетка')->assertDontSee('Вимкнений')->getContent();

        $this->assertLessThan(strpos($html, 'Про нас'), strpos($html, 'Зовнішній'));
        $this->assertStringContainsString('target="_blank" rel="noopener"', $html);
        $this->assertStringContainsString('href="'.url('/about-test').'"', $html);
    }

    public function test_nested_header_items_render_as_dropdown_and_footer_is_flat(): void
    {
        $this->withoutVite();
        MenuItem::query()->delete();
        $parent = $this->item('header', ['title' => 'Послуги', 'url' => '/services'], 0);
        $this->item('header', ['title' => 'SEO', 'url' => '/seo', 'parent_id' => $parent->id, 'depth' => 2], 1);
        $footerParent = $this->item('footer', ['title' => 'Компанія', 'url' => '/company'], 0);
        $this->item('footer', ['title' => 'Контакти', 'url' => '/contacts', 'parent_id' => $footerParent->id, 'depth' => 2], 1);

        $this->get('/')->assertOk()
            ->assertSee('class="dropdown"', false)->assertSee('class="dropdown-list"', false)
            ->assertSee('SEO')->assertSee('Контакти');
    }

    public function test_item_label_falls_back_to_page_title(): void
    {
        $page = Page::factory()->create(['title' => 'Назва сторінки']);

        $this->assertSame('Назва сторінки', $this->item('header', ['page_id' => $page->id])->label);
        $this->assertSame('Своя', $this->item('header', ['page_id' => $page->id, 'title' => 'Своя'])->label);
    }

    public function test_admin_creates_menu_item_for_a_page(): void
    {
        $admin = User::factory()->admin()->create();
        $page = Page::factory()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/header-menu', ['menu' => 'header', 'page_id' => $page->id, 'is_active' => 1])->assertRedirect();

        $this->assertDatabaseHas('menu_items', ['menu' => 'header', 'page_id' => $page->id]);
    }

    public function test_menu_item_needs_a_target_and_a_safe_url(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/footer-menu', ['menu' => 'footer'])
            ->assertSessionHasErrors(['title']);
        $this->actingAs($admin, 'backpack')->post('/admin/footer-menu', ['menu' => 'footer', 'title' => 'X', 'url' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('url');
    }

    public function test_admin_reorders_items_into_a_tree(): void
    {
        $admin = User::factory()->admin()->create();
        MenuItem::query()->delete();
        [$a, $b] = [$this->item('header', ['title' => 'A', 'url' => '/a'], 0), $this->item('header', ['title' => 'B', 'url' => '/b'], 1)];

        $tree = [
            ['item_id' => $b->id, 'parent_id' => null, 'depth' => 1, 'left' => 1, 'right' => 4],
            ['item_id' => $a->id, 'parent_id' => $b->id, 'depth' => 2, 'left' => 2, 'right' => 3],
        ];

        $this->actingAs($admin, 'backpack')->post('/admin/header-menu/reorder', ['tree' => json_encode($tree)])->assertOk();

        $this->assertSame($b->id, $a->fresh()->parent_id);
        $this->assertSame(1, $b->fresh()->lft);
    }

    public function test_manager_cannot_edit_menus(): void
    {
        $manager = User::factory()->create();

        $this->actingAs($manager, 'backpack')->get('/admin/header-menu')->assertForbidden();
    }

    public function test_admin_toggles_menu_item_inline(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->item('header', ['title' => 'A', 'url' => '/a']);

        $this->actingAs($admin, 'backpack')->postJson("/admin/header-menu/{$item->id}/toggle")->assertOk()->assertJson(['value' => false]);
        $this->assertFalse($item->fresh()->is_active);
    }

    public function test_toggle_is_scoped_to_its_own_menu(): void
    {
        $admin = User::factory()->admin()->create();
        $item = $this->item('footer', ['title' => 'A', 'url' => '/a']);

        $this->actingAs($admin, 'backpack')->postJson("/admin/header-menu/{$item->id}/toggle")->assertNotFound();
    }

    public function test_footer_groups_become_the_legal_row_and_loose_items_go_to_the_nav_row(): void
    {
        $this->withoutVite();
        MenuItem::query()->delete();
        $this->item('footer', ['title' => 'Тарифи', 'url' => '/#pricing'], 0);
        $group = $this->item('footer', ['title' => 'Документи'], 1);
        $this->item('footer', ['title' => 'Оферта', 'url' => '/publichna-oferta', 'parent_id' => $group->id, 'depth' => 2], 2);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('class="foot-nav"', $html);
        $this->assertStringContainsString('href="/#pricing"', $html);
        $this->assertStringContainsString('class="foot-legal"', $html);
        $this->assertStringContainsString('href="/publichna-oferta"', $html);
    }

    public function test_seeded_footer_pages_are_published_and_linked(): void
    {
        $this->withoutVite();

        foreach (['/pro-nas', '/polityka-konfidentsiynosti', '/publichna-oferta', '/services/avtomatychna-kupivlia-monet-nbu', '/services/profili-adspower', '/services/rezym-sposterezennia', '/services/litsenziia-na-prystriy', '/services/zurnal-zavdan'] as $path) {
            $this->get($path)->assertOk();
        }

        $this->get('/')->assertSee('href="'.url('/services/avtomatychna-kupivlia-monet-nbu').'"', false)->assertSee('href="'.url('/polityka-konfidentsiynosti').'"', false);
    }

    public function test_contacts_column_shows_filled_contacts(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['contact_email' => 'hello@example.com', 'telegram' => '@nbu_desktop']);

        $this->get('/')->assertSee('class="foot-contacts-row"', false)->assertSee('mailto:hello@example.com', false)->assertSee('https://t.me/nbu_desktop', false);
    }

    public function test_current_page_is_marked_active_in_header_menu(): void
    {
        $this->withoutVite();

        $this->get('/pro-nas')->assertOk()
            ->assertSeeInOrder(['class="is-active" aria-current="page"', 'Про нас'], false);
        $this->get('/')->assertOk()->assertDontSee('aria-current="page"', false);
    }

    public function test_menu_item_is_current_for_own_path_and_nested_paths_but_not_for_anchors(): void
    {
        $item = new MenuItem(['menu' => 'header', 'title' => 'Монети', 'url' => '/monety-nbu']);
        $anchor = new MenuItem(['menu' => 'header', 'title' => 'Тарифи', 'url' => '/#pricing']);

        $this->assertTrue($item->isCurrent('monety-nbu'));
        $this->assertTrue($item->isCurrent('monety-nbu/moneta-x'));
        $this->assertFalse($item->isCurrent('monety'));
        $this->assertFalse($item->isCurrent('/'));
        $this->assertFalse($anchor->isCurrent('/'));
    }
}
