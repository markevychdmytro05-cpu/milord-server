<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\LicenseAudit;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_license_edit_records_actor_and_before_and_after_values(): void
    {
        $manager = User::factory()->create();
        $package = Package::factory()->create();
        $license = License::factory()->for($package)->create(['customer' => 'Original']);

        $this->actingAs($manager, 'backpack')->put("/admin/license/{$license->id}", [
            'id' => $license->id,
            'package_id' => $package->id,
            'customer' => 'Updated',
            'status' => License::STATUS_REVOKED,
            'expires_at' => $license->expires_at->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        $audit = LicenseAudit::where('event', 'updated')->sole();
        $this->assertSame($manager->id, $audit->actor_id);
        $this->assertSame($manager->name, $audit->actor_name);
        $this->assertSame(['old' => 'Original', 'new' => 'Updated'], $audit->changes['customer']);
        $this->assertSame(['old' => 'active', 'new' => 'revoked'], $audit->changes['status']);
        $this->assertArrayNotHasKey('key', $audit->changes);
    }

    public function test_edit_uses_route_license_instead_of_forged_body_id(): void
    {
        $package = Package::factory()->create();
        $license = License::factory()->for($package)->create(['customer' => 'First']);
        $other = License::factory()->for($package)->create(['customer' => 'Second']);

        $this->actingAs(User::factory()->create(), 'backpack')->put("/admin/license/{$license->id}", [
            'id' => $other->id, 'package_id' => $package->id, 'customer' => 'Changed', 'status' => 'active',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Changed', $license->fresh()->customer);
        $this->assertSame('Second', $other->fresh()->customer);
        $this->assertSame($license->id, LicenseAudit::where('event', 'updated')->sole()->license_id);
    }

    public function test_audit_survives_license_and_actor_deletion(): void
    {
        $admin = User::factory()->admin()->create();
        $license = License::factory()->create();
        $this->actingAs($admin, 'backpack')->delete("/admin/license/{$license->id}")->assertOk();
        $this->assertModelMissing($license);

        $admin->delete();

        $audit = LicenseAudit::where('event', 'deleted')->sole();
        $this->assertNull($audit->license_id);
        $this->assertNull($audit->actor_id);
        $this->assertSame($license->id, $audit->license_number);
        $this->assertSame($admin->name, $audit->actor_name);
    }

    public function test_reset_devices_logs_each_removed_device(): void
    {
        $license = License::factory()->create(['max_devices' => 2]);
        $license->activations()->create(['device_id' => 'device-first', 'device_name' => 'Office']);
        $license->activations()->create(['device_id' => 'device-second', 'device_name' => 'Home']);
        $manager = User::factory()->create();

        $this->actingAs($manager, 'backpack')->post("/admin/license/{$license->id}/reset-devices")->assertRedirect();

        $this->assertSame(0, $license->activations()->count());
        $audits = LicenseAudit::where('event', 'device_unbound')->orderBy('id')->get();
        $this->assertCount(2, $audits);
        $this->assertSame('device-first', $audits[0]->metadata['device_id']);
        $this->assertSame('device-second', $audits[1]->metadata['device_id']);
        $this->assertSame($manager->id, $audits[0]->actor_id);
    }

    public function test_activation_list_delete_is_also_audited(): void
    {
        $license = License::factory()->create();
        $activation = $license->activations()->create(['device_id' => 'device-first']);

        $this->actingAs(User::factory()->create(), 'backpack')
            ->delete("/admin/license-activation/{$activation->id}")->assertOk();

        $this->assertModelMissing($activation);
        $this->assertSame('device-first', LicenseAudit::where('event', 'device_unbound')->sole()->metadata['device_id']);
    }

    public function test_unbind_cannot_remove_device_from_another_license(): void
    {
        $license = License::factory()->create();
        $other = License::factory()->create();
        $activation = $other->activations()->create(['device_id' => 'device-other']);

        $this->actingAs(User::factory()->create(), 'backpack')
            ->post("/admin/license/{$license->id}/devices/{$activation->id}/unbind")->assertNotFound();

        $this->assertModelExists($activation);
        $this->assertSame(0, LicenseAudit::where('event', 'device_unbound')->count());
    }

    public function test_global_journal_is_read_only_and_admin_only(): void
    {
        $audit = LicenseAudit::factory()->create();
        $this->actingAs(User::factory()->create(), 'backpack')->get('/admin/license-audit')->assertForbidden();
        $this->get("/admin/license-audit/{$audit->id}/show")->assertForbidden();

        $this->actingAs(User::factory()->admin()->create(), 'backpack')->get('/admin/license-audit')->assertOk();
        $this->get("/admin/license-audit/{$audit->id}/show")->assertOk()->assertSee('Ліцензію змінено');
        $this->delete("/admin/license-audit/{$audit->id}")->assertNotFound();
        $this->put("/admin/license-audit/{$audit->id}", ['event' => 'deleted'])->assertNotFound();
        $this->assertModelExists($audit);
    }

    public function test_heartbeat_does_not_fill_audit_log(): void
    {
        $license = License::factory()->create();
        $data = ['key' => $license->key, 'device_id' => 'device-first'];
        $this->postJson('/api/v1/license/activate', $data)->assertOk();
        $count = LicenseAudit::count();

        $this->postJson('/api/v1/license/check', $data)->assertOk();
        $this->postJson('/api/v1/license/activate', $data)->assertOk();

        $this->assertSame($count, LicenseAudit::count());
    }
}
