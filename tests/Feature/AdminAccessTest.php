<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/license')->assertRedirect('/admin/login');
    }

    public function test_user_stays_logged_in_after_real_login(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'admin12345']);

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'admin12345'])
            ->assertRedirect('/admin/dashboard');

        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/license')->assertOk();
    }

    public function test_password_change_logs_out_existing_session(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'admin12345']);
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'admin12345']);
        $this->get('/admin/dashboard')->assertOk();

        $admin->update(['password' => 'another-pass']);
        $this->app['auth']->forgetGuards();

        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
    }

    public function test_registration_and_password_reset_are_disabled(): void
    {
        $this->get('/admin/register')->assertForbidden();
        $this->get('/admin/password/reset')->assertNotFound();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user, 'backpack')->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->assertGuest('backpack');
    }

    public function test_manager_can_manage_licenses_but_not_delete_or_see_users(): void
    {
        $manager = User::factory()->create();
        $license = License::factory()->create();

        $this->actingAs($manager, 'backpack');

        $this->get('/admin/dashboard')->assertOk();
        $this->get('/admin/license')->assertOk();
        $this->get('/admin/license/create')->assertOk();
        $this->get("/admin/license/{$license->id}/show")->assertOk();
        $this->get("/admin/license/{$license->id}/edit")->assertOk();
        $this->delete("/admin/license/{$license->id}")->assertForbidden();
        $this->get('/admin/user')->assertForbidden();

        $this->assertModelExists($license);
    }

    public function test_manager_creates_license_from_package(): void
    {
        $this->freezeSecond();
        $manager = User::factory()->create();
        $package = Package::factory()->create(['max_accounts' => 10, 'max_devices' => 2, 'price' => 200, 'duration_days' => 30]);

        $this->actingAs($manager, 'backpack')->post('/admin/license', [
            'package_id' => $package->id,
            'customer' => 'Client',
            'contact' => '@client_tg',
            'key' => 'HACK-HACK-HACK-HACK',
            'created_by' => 999,
            'max_accounts' => 9999,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $license = License::sole();
        $this->assertNotSame('HACK-HACK-HACK-HACK', $license->key);
        $this->assertSame($manager->id, $license->created_by);
        $this->assertSame($package->id, $license->package_id);
        $this->assertSame(10, $license->max_accounts);
        $this->assertSame(2, $license->max_devices);
        $this->assertSame(200, $license->price);
        $this->assertTrue($license->expires_at->equalTo(now()->addDays(30)));
    }

    public function test_discount_price_overrides_package_price(): void
    {
        $package = Package::factory()->lifetime()->create(['price' => 300]);

        $this->actingAs(User::factory()->create(), 'backpack')->post('/admin/license', [
            'package_id' => $package->id,
            'customer' => 'Client',
            'price' => 250,
        ])->assertSessionHasNoErrors();

        $license = License::sole();
        $this->assertSame(250, $license->price);
        $this->assertNull($license->expires_at);
    }

    public function test_license_requires_active_package_and_customer(): void
    {
        $inactive = Package::factory()->inactive()->create();

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post('/admin/license', ['package_id' => $inactive->id])
            ->assertSessionHasErrors(['package_id', 'customer']);

        $this->assertSame(0, License::count());
    }

    public function test_changing_package_does_not_affect_issued_licenses(): void
    {
        $package = Package::factory()->create(['max_accounts' => 5, 'price' => 100]);
        $license = License::factory()->create(['package_id' => $package->id, 'max_accounts' => null, 'price' => null]);

        $package->update(['max_accounts' => 50, 'price' => 999]);

        $this->assertSame(5, $license->fresh()->max_accounts);
        $this->assertSame(100, $license->fresh()->price);
    }

    public function test_packages_are_admin_only(): void
    {
        $this->actingAs(User::factory()->create(), 'backpack')->get('/admin/package')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create(), 'backpack')->get('/admin/package')->assertOk();
    }

    public function test_package_can_be_toggled_from_list(): void
    {
        $package = Package::factory()->create(['is_active' => true]);

        $this->actingAs(User::factory()->admin()->create(), 'backpack')
            ->postJson("/admin/package/{$package->id}/toggle")
            ->assertOk()->assertJson(['value' => false]);

        $this->assertFalse($package->fresh()->is_active);

        $this->actingAs(User::factory()->create(), 'backpack')
            ->postJson("/admin/package/{$package->id}/toggle")
            ->assertForbidden();
    }

    public function test_package_with_licenses_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $used = Package::factory()->create();
        License::factory()->create(['package_id' => $used->id]);
        $unused = Package::factory()->create();

        $this->actingAs($admin, 'backpack');

        $this->delete("/admin/package/{$used->id}")->assertStatus(409);
        $this->delete("/admin/package/{$unused->id}")->assertOk();
        $this->assertModelExists($used);
        $this->assertModelMissing($unused);
    }

    public function test_changing_package_updates_limits_but_not_key_or_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $start = Package::factory()->create(['max_accounts' => 5, 'max_devices' => 1, 'price' => 100]);
        $pro = Package::factory()->create(['max_accounts' => 25, 'max_devices' => 2, 'price' => 350]);
        $license = License::factory()->create(['package_id' => $start->id, 'max_accounts' => null, 'max_devices' => null, 'price' => null]);
        $expiresAt = $license->expires_at->toDateTimeString();

        $this->actingAs($admin, 'backpack')->put("/admin/license/{$license->id}", [
            'id' => $license->id,
            'key' => 'HACK-HACK-HACK-HACK',
            'package_id' => $pro->id,
            'customer' => 'Client',
            'status' => License::STATUS_ACTIVE,
            'expires_at' => $expiresAt,
            'max_accounts' => 9999,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fresh = $license->fresh();
        $this->assertSame($license->key, $fresh->key);
        $this->assertSame($pro->id, $fresh->package_id);
        $this->assertSame(25, $fresh->max_accounts);
        $this->assertSame(2, $fresh->max_devices);
        $this->assertSame(350, $fresh->price);
        $this->assertSame($expiresAt, $fresh->expires_at->toDateTimeString());
    }

    public function test_edit_keeps_discount_and_empty_price_resets_to_package_price(): void
    {
        $admin = User::factory()->admin()->create();
        $package = Package::factory()->create(['price' => 300]);
        $license = License::factory()->create(['package_id' => $package->id, 'price' => 250]);
        $payload = ['id' => $license->id, 'package_id' => $package->id, 'customer' => 'Client', 'status' => License::STATUS_ACTIVE];

        $this->actingAs($admin, 'backpack');

        $this->put("/admin/license/{$license->id}", $payload + ['price' => 250])->assertSessionHasNoErrors();
        $this->assertSame(250, $license->fresh()->price);

        $this->put("/admin/license/{$license->id}", $payload + ['price' => ''])->assertSessionHasNoErrors();
        $this->assertSame(300, $license->fresh()->price);
    }

    public function test_reset_devices_unbinds_all_activations(): void
    {
        $manager = User::factory()->create();
        $license = License::factory()->create(['max_devices' => 2]);
        $license->activations()->create(['device_id' => 'device-one-1']);
        $license->activations()->create(['device_id' => 'device-two-2']);

        $this->actingAs($manager, 'backpack')
            ->post("/admin/license/{$license->id}/reset-devices")
            ->assertRedirect();

        $this->assertSame(0, $license->activations()->count());
    }

    public function test_show_page_lists_devices_and_single_device_can_be_unbound(): void
    {
        $manager = User::factory()->create();
        $license = License::factory()->create(['max_devices' => 2]);
        $keep = $license->activations()->create(['device_id' => 'device-keep-1', 'device_name' => 'Office PC']);
        $drop = $license->activations()->create(['device_id' => 'device-drop-2', 'device_name' => 'Laptop']);

        $this->actingAs($manager, 'backpack');

        $this->get("/admin/license/{$license->id}/show")->assertOk()->assertSee($license->key)->assertSee('Office PC')->assertSee('Laptop');
        $this->post("/admin/license/{$license->id}/devices/{$drop->id}/unbind")->assertRedirect();

        $this->assertModelExists($keep);
        $this->assertModelMissing($drop);
    }

    public function test_admin_can_delete_license_and_manage_users(): void
    {
        $admin = User::factory()->admin()->create();
        $license = License::factory()->create();

        $this->actingAs($admin, 'backpack');

        $this->get('/admin/user')->assertOk();
        $this->delete("/admin/license/{$license->id}")->assertOk();
        $this->assertModelMissing($license);
    }

    public function test_admin_cannot_demote_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backpack');

        $this->put("/admin/user/{$admin->id}", [
            'id' => $admin->id,
            'name' => 'Renamed',
            'email' => $admin->email,
            'role_name' => User::ROLE_MANAGER,
            'is_active' => 0,
        ])->assertRedirect();

        $fresh = $admin->fresh();
        $this->assertSame('Renamed', $fresh->name);
        $this->assertTrue($fresh->isAdmin());
        $this->assertTrue($fresh->is_active);

        $this->delete("/admin/user/{$admin->id}")->assertForbidden();
    }

    public function test_admin_creates_manager(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'backpack')->post('/admin/user', [
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'role_name' => User::ROLE_MANAGER,
            'is_active' => 1,
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertRedirect();

        $manager = User::where('email', 'manager@example.com')->sole();
        $this->assertFalse($manager->isAdmin());
        $this->assertTrue(password_verify('secret-pass', $manager->password));
    }
}
