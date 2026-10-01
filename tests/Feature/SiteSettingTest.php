<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'header_logo' => 'Мій логотип',
            'footer_text' => 'Підвал тут',
        ];
    }

    public function test_public_layout_renders_settings_from_database(): void
    {
        $this->withoutVite();
        SiteSetting::current()->update($this->payload());

        $this->get('/')
            ->assertOk()
            ->assertSee('Мій логотип')
            ->assertSee('Підвал тут')
            ->assertDontSee('kyiv-time');
    }

    public function test_admin_can_open_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($admin, 'backpack')->get("/admin/site-setting/{$id}/edit")->assertOk()->assertSee('Логотип');
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->admin()->create();
        $id = SiteSetting::current()->id;

        $this->withoutExceptionHandling()->actingAs($admin, 'backpack')->put("/admin/site-setting/{$id}", $this->payload())->assertRedirect();

        $this->assertDatabaseHas('site_settings', ['id' => $id, 'header_logo' => 'Мій логотип', 'footer_text' => 'Підвал тут']);
    }

    public function test_manager_cannot_edit_settings(): void
    {
        $manager = User::factory()->create();
        $id = SiteSetting::current()->id;

        $this->actingAs($manager, 'backpack')->get("/admin/site-setting/{$id}/edit")->assertForbidden();
    }
}
