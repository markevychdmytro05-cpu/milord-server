<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_super_admin_with_all_permissions(): void
    {
        $this->artisan('admin:create-super', ['email' => 'boss@example.com', '--password' => 'secret-pass-1'])->assertSuccessful();

        $user = User::where('email', 'boss@example.com')->firstOrFail();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->isAdmin());
        $this->assertSame(Permission::count(), $user->permissions->count());
        $this->assertTrue($user->can('site_manage'));
        $this->assertTrue($user->can('licenses_delete'));
    }

    public function test_existing_user_is_promoted_and_keeps_password_without_option(): void
    {
        $user = User::factory()->inactive()->create(['email' => 'old@example.com', 'password' => 'keep-me-123']);
        $hash = $user->password;

        $this->artisan('admin:create-super', ['email' => 'old@example.com'])->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertSame($hash, $user->password);
        $this->assertSame(Permission::count(), $user->permissions->count());
    }

    public function test_rejects_short_password(): void
    {
        $this->artisan('admin:create-super', ['email' => 'x@example.com', '--password' => 'short'])->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
