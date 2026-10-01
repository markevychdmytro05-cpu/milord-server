<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\LicenseAudit;
use App\Models\LicensePayment;
use App\Models\Package;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AccessManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** Адміністратор без одного права – через окрему роль. */
    private function adminWithout(string $permission): User
    {
        $role = Role::factory()->create();
        $role->syncPermissions(Permission::where('name', '!=', $permission)->pluck('name'));

        return User::factory()->withRole($role->name)->create();
    }

    public function test_direct_permission_adds_access_on_top_of_the_role(): void
    {
        $user = User::factory()->create();
        $this->assertFalse(Gate::forUser($user)->allows('licenses_delete'));
        $user->givePermissionTo('licenses_delete');
        $this->assertTrue(Gate::forUser($user->fresh())->allows('licenses_delete'));
    }

    public function test_permissions_live_in_a_separate_table_with_name_and_title(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'licenses_view', 'title' => 'Перегляд ліцензій', 'group' => 'Ліцензії']);
        $this->assertSame(38, Permission::count());
    }

    public function test_default_roles_preserve_existing_access(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->create();
        $managerRights = [
            'dashboard_view', 'licenses_view', 'licenses_create', 'licenses_update',
            'license_history_view', 'devices_view', 'devices_unbind', 'payments_view', 'payments_create',
        ];
        foreach (Permission::pluck('name') as $permission) {
            $this->assertTrue($admin->can($permission), $permission);
            $this->assertSame(in_array($permission, $managerRights, true), $manager->can($permission), $permission);
        }
        $this->assertFalse($admin->can('unknown_permission'));
    }

    public function test_inactive_user_is_denied_even_with_a_personal_grant(): void
    {
        $user = User::factory()->inactive()->withPermissions(['licenses_delete'])->create();
        $this->assertFalse($user->can('licenses_delete'));
        $this->actingAs($user, 'backpack')->get('/admin/license')->assertRedirect('/admin/login');
    }

    public static function protectedEndpoints(): array
    {
        return [
            'license list' => ['licenses_view', 'GET', '/admin/license', null],
            'license search' => ['licenses_view', 'POST', '/admin/license/search', null],
            'license show' => ['licenses_view', 'GET', '/admin/license/{id}/show', 'license'],
            'license create form' => ['licenses_create', 'GET', '/admin/license/create', null],
            'license store' => ['licenses_create', 'POST', '/admin/license', null],
            'license update' => ['licenses_update', 'PUT', '/admin/license/{id}', 'license'],
            'license delete' => ['licenses_delete', 'DELETE', '/admin/license/{id}', 'license'],
            'activation list' => ['devices_view', 'GET', '/admin/license-activation', null],
            'activation delete' => ['devices_unbind', 'DELETE', '/admin/license-activation/{id}', 'activation'],
            'reset devices' => ['devices_unbind', 'POST', '/admin/license/{id}/reset-devices', 'license'],
            'unbind device' => ['devices_unbind', 'POST', '/admin/license/{license}/devices/{id}/unbind', 'activation'],
            'record payment' => ['payments_create', 'POST', '/admin/license/{id}/payments', 'license'],
            'void payment' => ['payments_void', 'POST', '/admin/license/{license}/payments/{id}/void', 'payment'],
            'package list' => ['packages_view', 'GET', '/admin/package', null],
            'package store' => ['packages_create', 'POST', '/admin/package', null],
            'package update' => ['packages_update', 'PUT', '/admin/package/{id}', 'package'],
            'package delete' => ['packages_delete', 'DELETE', '/admin/package/{id}', 'package'],
            'package toggle' => ['packages_update', 'POST', '/admin/package/{id}/toggle', 'package'],
            'user list' => ['users_view', 'GET', '/admin/user', null],
            'user store' => ['access_manage', 'POST', '/admin/user', null],
            'user update' => ['access_manage', 'PUT', '/admin/user/{id}', 'user'],
            'user delete' => ['access_manage', 'DELETE', '/admin/user/{id}', 'user'],
            'role list' => ['access_manage', 'GET', '/admin/role', null],
            'role store' => ['access_manage', 'POST', '/admin/role', null],
            'role update' => ['access_manage', 'PUT', '/admin/role/{id}', 'role'],
            'role delete' => ['access_manage', 'DELETE', '/admin/role/{id}', 'role'],
            'audit list' => ['audit_view', 'GET', '/admin/license-audit', null],
            'audit show' => ['audit_view', 'GET', '/admin/license-audit/{id}/show', 'audit'],
        ];
    }

    #[DataProvider('protectedEndpoints')]
    public function test_direct_request_returns_403_without_the_required_permission(string $permission, string $method, string $url, ?string $kind): void
    {
        $actor = $this->adminWithout($permission);
        $target = match ($kind) {
            'license' => License::factory()->create(),
            'activation' => License::factory()->create()->activations()->create(['device_id' => 'permission-test-device']),
            'payment' => LicensePayment::factory()->create(),
            'package' => Package::factory()->create(),
            'user' => User::factory()->create(),
            'role' => Role::factory()->create(),
            'audit' => LicenseAudit::factory()->create(),
            default => null,
        };
        $url = str_replace(['{id}', '{license}'], [(string) $target?->id, (string) ($target?->license_id ?? '')], $url);
        $this->actingAs($actor, 'backpack')->call($method, $url)->assertForbidden();
        if ($target) {
            $this->assertModelExists($target);
        }
    }

    public function test_role_and_personal_permissions_can_be_saved_in_admin_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backpack')->post('/admin/role', [
            'name' => 'support', 'title' => 'Підтримка', 'permission_names' => ['licenses_view', 'devices_view'],
            'is_system' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $role = Role::where('name', 'support')->sole();
        $this->assertFalse($role->is_system);
        $this->assertEqualsCanonicalizing(['licenses_view', 'devices_view'], $role->permission_names);

        $this->post('/admin/user', [
            'name' => 'Support Agent', 'email' => 'support@example.invalid', 'password' => 'support-pass-123',
            'password_confirmation' => 'support-pass-123', 'role_name' => 'support', 'is_active' => 1,
            'permission_names' => ['licenses_update'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $user = User::where('email', 'support@example.invalid')->sole();
        $this->assertTrue($user->can('licenses_view'));
        $this->assertTrue($user->can('licenses_update'));
        $this->assertTrue($user->can('devices_view'));
        $this->assertFalse($user->can('access_manage'));
        $this->flushSession();
        $this->actingAs($user, 'backpack')->get('/admin/license-activation')->assertOk();
        $this->get('/admin/license')->assertOk();
    }

    public function test_changing_role_permissions_preserves_personal_permissions(): void
    {
        $role = Role::factory()->withPermissions(['licenses_view'])->create();
        $inheriting = User::factory()->withRole($role->name)->create();
        $extra = User::factory()->withRole($role->name)->withPermissions(['packages_view'])->create();
        $this->actingAs(User::factory()->admin()->create(), 'backpack')->put('/admin/role/'.$role->id, [
            'id' => 9999, 'title' => 'Оператори', 'name' => 'changed-code', 'permission_names' => ['devices_view'],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertNotSame('changed-code', $role->fresh()->name);
        $this->assertSame('Оператори', $role->fresh()->title);
        $this->assertFalse($inheriting->fresh()->can('licenses_view'));
        $this->assertTrue($inheriting->fresh()->can('devices_view'));
        $this->assertTrue($extra->fresh()->can('packages_view'));
    }

    public function test_admin_can_change_another_users_personal_permissions_and_empty_list_clears_them(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $data = ['name' => $other->name, 'email' => $other->email, 'role_name' => 'manager', 'is_active' => 1];
        $this->actingAs($admin, 'backpack')->put('/admin/user/'.$other->id, $data + ['permission_names' => ['licenses_delete']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($other->fresh()->can('licenses_delete'));

        $this->put('/admin/user/'.$other->id, $data + ['permission_names' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($other->fresh()->can('licenses_delete'));
        $this->assertTrue($other->fresh()->can('licenses_view'));
    }

    public function test_user_update_uses_route_id_and_cannot_disable_the_current_admin_through_body_id(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $this->actingAs($admin, 'backpack')->put('/admin/user/'.$other->id, [
            'id' => $admin->id, 'name' => 'Actual route target', 'email' => 'route-target@example.invalid',
            'role_name' => 'manager', 'is_active' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Actual route target', $other->fresh()->name);
        $this->assertFalse($other->fresh()->is_active);
        $this->assertTrue($admin->fresh()->isAdmin());
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_current_user_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backpack')->put('/admin/user/'.$admin->id, [
            'name' => $admin->name, 'email' => $admin->email, 'role_name' => 'manager', 'is_active' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_editing_own_role_cannot_remove_the_ability_to_restore_access(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('name', 'admin')->sole();
        $this->actingAs($admin, 'backpack')->put('/admin/role/'.$role->id, [
            'title' => 'Адміністратор', 'permission_names' => ['licenses_view'],
        ])->assertSessionHasErrors(['permission_names' => 'Не можна забрати у своєї ролі право керування доступом без особистого дозволу.']);
        $this->assertContains('access_manage', $role->fresh()->permission_names);
    }

    public function test_personal_grant_can_preserve_access_management_while_own_role_is_restricted(): void
    {
        $admin = User::factory()->admin()->withPermissions(['access_manage'])->create();
        $role = Role::where('name', 'admin')->sole();
        $this->actingAs($admin, 'backpack')->put('/admin/role/'.$role->id, [
            'title' => 'Обмежена роль', 'permission_names' => ['licenses_view'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['licenses_view'], $role->fresh()->permission_names);
        $this->assertTrue($admin->fresh()->can('access_manage'));
    }

    public function test_used_and_standard_roles_cannot_be_deleted_but_an_unused_custom_role_can(): void
    {
        $used = Role::factory()->create();
        User::factory()->withRole($used->name)->create();
        $unused = Role::factory()->create();
        $standard = Role::where('name', 'manager')->sole();
        $this->actingAs(User::factory()->admin()->create(), 'backpack');
        $this->delete('/admin/role/'.$used->id)->assertStatus(409);
        $this->delete('/admin/role/'.$standard->id)->assertStatus(409);
        $this->delete('/admin/role/'.$unused->id)->assertOk();
        $this->assertModelExists($used);
        $this->assertModelExists($standard);
        $this->assertModelMissing($unused);
    }

    public function test_unknown_permission_names_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $this->actingAs($admin, 'backpack')->put('/admin/user/'.$target->id, [
            'name' => $target->name, 'email' => $target->email, 'role_name' => 'manager', 'is_active' => 1,
            'permission_names' => ['unknown_permission'],
        ])->assertSessionHasErrors(['permission_names.0' => 'Особисті права містять невідомий дозвіл.']);
        $this->post('/admin/role', ['name' => 'invalid', 'title' => 'Invalid', 'permission_names' => ['unknown_permission']])
            ->assertSessionHasErrors(['permission_names.0' => 'Роль містить невідомий дозвіл.']);
        $this->assertSame([], $target->fresh()->permission_names);
        $this->assertDatabaseMissing('roles', ['name' => 'invalid']);
        $this->assertDatabaseMissing('permissions', ['name' => 'unknown_permission']);
    }

    public function test_read_only_user_cannot_grant_themselves_permissions_through_account_info(): void
    {
        $user = User::factory()->withPermissions(['users_view'])->create();
        $this->actingAs($user, 'backpack')->post('/admin/edit-account-info', [
            'name' => 'Updated Name', 'email' => $user->email, 'role_name' => 'admin',
            'permission_names' => ['access_manage'],
        ])->assertRedirect();
        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertFalse($user->fresh()->can('access_manage'));
        $this->get('/admin/user')->assertOk();
        $this->get('/admin/user/create')->assertForbidden();
    }

    public function test_restricted_data_is_hidden_from_dashboard_license_and_audit(): void
    {
        $role = Role::factory()->create();
        $role->syncPermissions(Permission::whereNotIn('name', ['payments_view', 'payments_create', 'devices_view'])->pluck('name'));
        $user = User::factory()->withRole($role->name)->create();
        $license = License::factory()->create();
        $license->activations()->create(['device_id' => 'private-device', 'device_name' => 'PRIVATE-DEVICE-NAME']);
        LicensePayment::factory()->for($license)->create(['note' => 'PRIVATE-PAYMENT-NOTE']);
        $paymentAudit = LicenseAudit::factory()->create(['license_id' => $license->id, 'event' => 'payment_recorded', 'metadata' => ['reference' => 'PRIVATE-PAYMENT-AUDIT']]);
        $this->actingAs($user, 'backpack')->get('/admin/dashboard')->assertOk()
            ->assertDontSee('Отримано оплат цього місяця')->assertDontSee('Пристроїв онлайн за 24 год');
        $this->get('/admin/license/'.$license->id.'/show')->assertOk()
            ->assertDontSee('PRIVATE-DEVICE-NAME')->assertDontSee('PRIVATE-PAYMENT-NOTE')->assertDontSee('PRIVATE-PAYMENT-AUDIT');
        $this->get('/admin/license-audit/'.$paymentAudit->id.'/show')->assertNotFound();
    }

    public function test_menu_and_fields_use_backpack_user_even_when_default_guard_is_web(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'backpack');
        Auth::shouldUse('web');
        $this->get('/admin/user/'.$admin->id.'/edit')->assertOk()->assertSee('Додаткові права')
            ->assertSee('permission-picker', false)->assertSee('Ролі та права');
    }

    public function test_without_dashboard_permission_user_lands_in_an_allowed_section(): void
    {
        $role = Role::factory()->withPermissions(['licenses_view'])->create();
        $user = User::factory()->withRole($role->name)->create();
        $this->actingAs($user, 'backpack')->get('/admin/dashboard')->assertRedirect('/admin/license');
    }

    public function test_guest_cannot_open_or_change_roles(): void
    {
        $this->get('/admin/role')->assertRedirect('/admin/login');
        $this->post('/admin/role', ['name' => 'guest', 'title' => 'Guest', 'permission_names' => ['access_manage']])->assertRedirect('/admin/login');
        $this->assertDatabaseMissing('roles', ['name' => 'guest']);
    }

    public function test_empty_permissions_are_saved_as_an_empty_role(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'backpack')->post('/admin/role', [
            'name' => 'empty', 'title' => 'Без доступу', 'permission_names' => '',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([], Role::where('name', 'empty')->sole()->permission_names);
    }

    public function test_manager_can_delete_license_when_individually_granted_permission(): void
    {
        $manager = User::factory()->withPermissions(['licenses_delete'])->create();
        $license = License::factory()->create();
        $this->actingAs($manager, 'backpack')->delete('/admin/license/'.$license->id)->assertOk();
        $this->assertModelMissing($license);
    }

    public function test_delegated_access_manager_can_manage_roles_without_admin_role(): void
    {
        $manager = User::factory()->withPermissions(['access_manage'])->create();
        $this->actingAs($manager, 'backpack')->get('/admin/role')->assertOk();
        $this->post('/admin/role', ['name' => 'delegated', 'title' => 'Делегована', 'permission_names' => ['licenses_view']])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('roles', ['name' => 'delegated']);
        $this->assertFalse($manager->isAdmin());
    }

    public function test_create_only_role_lands_on_creation_form_and_cannot_read_lists(): void
    {
        $role = Role::factory()->withPermissions(['licenses_create'])->create();
        $user = User::factory()->withRole($role->name)->create();
        $this->actingAs($user, 'backpack')->get('/admin/dashboard')->assertRedirect('/admin/license/create');
        $this->get('/admin/license/create')->assertOk();
        $this->get('/admin/license')->assertForbidden();
    }

    public function test_role_edit_form_displays_the_permission_picker_with_assigned_permissions(): void
    {
        $role = Role::factory()->withPermissions(['devices_view'])->create();
        $this->actingAs(User::factory()->admin()->create(), 'backpack')->get('/admin/role/'.$role->id.'/edit')
            ->assertOk()->assertSee('Права ролі')->assertSee('data-list="assigned"', false)
            ->assertSee('data-name="devices_view"', false);
    }

    public function test_removing_last_access_manager_rolls_back_all_access_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('name', 'admin')->sole();
        $this->actingAs($admin, 'backpack');

        try {
            AccessManagement::run(function () use ($admin, $role): void {
                $admin->update(['is_active' => false]);
                $role->update(['title' => 'Must roll back']);
            });
            $this->fail('The last access manager must be preserved.');
        } catch (ValidationException $exception) {
            $this->assertSame(['Має залишитися хоча б один активний користувач із правом керування доступом.'], $exception->errors()['role']);
        }
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame('Адміністратор', $role->fresh()->title);
    }

    public function test_access_management_rechecks_actor_after_another_request_revokes_access(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->create();
        $this->actingAs($admin, 'backpack');
        $admin->fresh()->update(['is_active' => false]);
        $this->assertTrue($admin->can('access_manage'));

        $this->post('/admin/role', ['name' => 'forbidden', 'title' => 'Forbidden', 'permission_names' => ['access_manage']])->assertForbidden();
        $this->delete('/admin/user/'.$other->id)->assertForbidden();
        $this->assertDatabaseMissing('roles', ['name' => 'forbidden']);
        $this->assertModelExists($other);
    }
}
