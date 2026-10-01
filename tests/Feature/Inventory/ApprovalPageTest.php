<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvTagLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApprovalPageTest extends TestCase
{
    private function pending(Asset $asset): InvChange
    {
        return InvChange::create([
            'asset_id' => $asset->id, 'inv_snapshot_id' => 1, 'section' => 'memories', 'part_key' => '1|OLD',
            'change_type' => 'removed', 'old_value' => json_encode(['part_key' => '1|OLD', 'serial' => 'OLD', 'capacity_mb' => 8192]),
            'severity' => 'warning', 'state' => 'pending',
        ]);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->actingAs(User::factory()->create())->get(route('inventory.approvals'))->assertForbidden();
    }

    public function test_admin_sees_queue_and_can_approve_and_reject(): void
    {
        $asset = Asset::factory()->create();
        DB::table('inv_memories')->insert(['asset_id' => $asset->id, 'part_key' => '1|OLD', 'serial' => 'OLD', 'is_current' => true]);
        $c = $this->pending($asset);
        InvTagLocation::create(['tag' => 'PHONG-X', 'state' => 'pending', 'agent_count' => 3]);
        $admin = User::factory()->superuser()->create();

        $this->actingAs($admin)->get(route('inventory.approvals'))->assertOk()->assertSee('PHONG-X')->assertSee('1|OLD');

        $this->actingAs($admin)->post(route('inventory.changes.approve', $c))->assertRedirect(route('inventory.approvals'));
        $this->assertSame('approved', $c->fresh()->state);
        $this->assertSame(1, DB::table('inv_removed_parts')->count());

        $this->actingAs($admin)->get(route('inventory.removed_parts'))->assertOk()->assertSee('OLD');
    }

    public function test_admin_can_create_new_location_from_tag(): void
    {
        $tag = InvTagLocation::create(['tag' => 'PHONG-MOI', 'state' => 'pending']);

        $this->actingAs(User::factory()->create(['permissions' => json_encode(['inventory.approve' => '1'])]))
            ->post(route('inventory.tags.assign', $tag), ['new_location_name' => 'Phòng Mới'])
            ->assertRedirect();

        $this->assertSame('approved', $tag->fresh()->state);
        $this->assertNotNull(Location::where('name', 'Phòng Mới')->first());
    }

    public function test_critical_change_sends_email(): void
    {
        Mail::fake();
        config(['inventory.alert_email' => 'admin@example.test']);

        $asset = Asset::factory()->create();
        $detector = app(\App\Services\Inventory\Changes\ChangeDetector::class);
        DB::table('inv_bios')->insert(['asset_id' => $asset->id, 'part_key' => 'single', 'msn' => 'MB-1', 'is_current' => true]);

        $detector->filter($asset->id, 1, 'bios', 'inv_bios', [['part_key' => 'single', 'msn' => 'MB-2']]);

        $this->assertTrue(InvChange::where('severity', 'critical')->exists());
    }
}
