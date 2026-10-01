<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_editor_can_open_file_manager(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->get('/admin/elfinder')->assertOk();
    }

    public function test_user_without_content_permissions_cannot_use_file_manager(): void
    {
        $user = User::factory()->withRole('manager')->create();

        $this->actingAs($user, 'backpack')->get('/admin/elfinder')->assertForbidden();
        $this->actingAs($user, 'backpack')->post('/admin/elfinder/connector', ['cmd' => 'open', 'init' => 1])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/elfinder')->assertRedirect('/admin/login');
    }

    public function test_only_safe_file_types_may_be_uploaded(): void
    {
        $options = config('elfinder.root_options');

        $this->assertSame(['all'], $options['uploadDeny']);
        $this->assertNotContains('text/x-php', $options['uploadAllow']);
        $this->assertContains('image/png', $options['uploadAllow']);
    }

    public function test_page_form_uses_the_file_manager_for_social_image(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->get('/admin/page/create')->assertOk()
            ->assertSee('data-elfinder-trigger-url', false)->assertSee('name="og_image"', false);
    }
}
