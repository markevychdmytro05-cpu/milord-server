<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\LocalUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_is_shown_only_when_enabled(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['show_topbar' => false]);
        $this->get('/')->assertDontSee('class="topbar"', false);

        SiteSetting::current()->update(['show_topbar' => true, 'contact_email' => 'hello@example.com', 'contact_phone' => '+38 (050) 111-22-33', 'contact_address' => 'Київ, вул. Тестова 1']);

        $this->get('/')->assertOk()
            ->assertSee('class="topbar"', false)->assertSee('mailto:hello@example.com', false)
            ->assertSee('href="tel:+380501112233"', false)->assertSee('Київ, вул. Тестова 1')->assertSee('has-topbar', false);
    }

    public function test_header_button_comes_from_settings(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['header_button_text' => 'Замовити дзвінок', 'header_button_url' => '/#contact']);

        $this->get('/')->assertSee('class="nav-cta" href="/#contact"', false)->assertSee('Замовити дзвінок');

        SiteSetting::current()->update(['header_button_text' => null, 'header_button_url' => null]);
        $this->get('/')->assertDontSee('nav-cta', false);
    }

    public function test_floating_messenger_bar_lists_configured_messengers(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['telegram' => '@nbu_desktop', 'viber' => '+380501112233']);

        $this->get('/')->assertSee('class="float-messengers"', false)
            ->assertSee('aria-label="Telegram"', false)->assertSee('aria-label="Viber"', false)->assertDontSee('aria-label="WhatsApp"', false);
    }

    public function test_hero_renders_points_two_buttons_and_image(): void
    {
        $this->withoutVite();
        $home = Page::where('slug', Page::ROOT_SLUG)->firstOrFail();
        $hero = Module::findOrFail($home->page_modules[0]);
        $setting = $hero->setting->toArray();
        $setting['image'] = '/uploads/hero.png';
        $hero->setting = $setting;
        $hero->save();

        $this->get('/')->assertOk()
            ->assertSee('class="hero-points"', false)->assertSee('Старт за київським часом')
            ->assertSee('class="btn ghost"', false)->assertSee('src="/uploads/hero.png"', false);
    }

    public function test_contact_form_is_sent_by_ajax_with_a_toast(): void
    {
        $this->withoutVite();

        $this->get('/')->assertSee('class="toast"', false)->assertSee('data-success=', false)->assertSee('name="website"', false);
    }

    public function test_local_urls_are_stored_as_paths(): void
    {
        $this->assertSame('/uploads/a.png', LocalUrl::path(url('/uploads/a.png')));
        $this->assertSame('https://cdn.example.com/a.png', LocalUrl::path('https://cdn.example.com/a.png'));
        $this->assertNull(LocalUrl::path(null));
    }

    public function test_admin_saves_header_and_contact_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($admin, 'backpack')->put("/admin/site-setting/{$id}", [
            'header_logo' => 'Логотип', 'footer_text' => 'Підвал', 'header_button_text' => 'Дзвінок', 'header_button_url' => '/#contact',
            'contact_email' => 'a@b.co', 'contact_phone' => '+380501112233', 'contact_address' => 'Київ',
        ])->assertRedirect();

        $this->assertDatabaseHas('site_settings', ['id' => $id, 'header_button_text' => 'Дзвінок', 'contact_email' => 'a@b.co']);
    }

    public function test_header_button_needs_both_text_and_safe_url(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($admin, 'backpack')->put("/admin/site-setting/{$id}", [
            'header_logo' => 'X', 'footer_text' => 'Y', 'header_button_text' => 'Дзвінок', 'header_button_url' => 'javascript:alert(1)', 'contact_email' => 'not-an-email',
        ])->assertSessionHasErrors(['header_button_url', 'contact_email']);
    }

    public function test_header_and_footer_show_the_built_in_logo_mark(): void
    {
        $this->withoutVite();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="logo-mark"'));
        $this->assertStringContainsString('href="'.asset('favicon.svg').'"', $html);
    }

    public function test_custom_logo_image_replaces_the_built_in_mark(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update(['logo_image' => '/uploads/logo.png']);

        $this->get('/')->assertSee('<img class="logo-mark" src="/uploads/logo.png"', false);
    }

    public function test_admin_saves_logo_image_as_a_path(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($admin, 'backpack')->put("/admin/site-setting/{$id}", [
            'header_logo' => 'Логотип', 'footer_text' => 'Підвал', 'logo_image' => url('/uploads/logo.png'),
        ])->assertRedirect();

        $this->assertSame('/uploads/logo.png', SiteSetting::current()->logo_image);
    }
}
