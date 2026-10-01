<?php

namespace Tests\Feature\Inventory;

use App\Jobs\Inventory\ProcessSnapshot;
use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvSnapshot;
use App\Models\Inventory\InvTagLocation;
use App\Models\Location;
use App\Models\Maintenance;
use App\Services\Inventory\Changes\ChangeApplier;
use App\Services\Inventory\Changes\TagLocationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChangeApprovalTest extends TestCase
{
    private Asset $asset;

    private InvAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asset = Asset::factory()->create(['asset_tag' => 'PC-AP', 'location_id' => null]);
        $this->agent = InvAgent::create(['deviceid' => 'PC-AP-1', 'state' => 'active', 'tag' => 'PHONG-KE-TOAN']);
    }

    private function report(array $memories, array $bios = ['msn' => 'MB-1', 'mmodel' => 'H610']): void
    {
        $content = [
            'hardware' => ['name' => 'PC-AP', 'uuid' => 'U-AP'],
            'bios' => $bios,
            'memories' => $memories,
            'networks' => [['mac' => 'AA:BB:CC:00:00:01', 'ipaddress' => '10.0.0.5']],
        ];
        $snap = InvSnapshot::create([
            'inv_agent_id' => $this->agent->id,
            'payload' => gzcompress(json_encode(['action' => 'inventory', 'content' => $content])),
            'content_hash' => uniqid(), 'protocol' => 'json', 'received_at' => now(),
        ]);
        (new ProcessSnapshot($snap->id))->handle(
            app(\App\Services\Inventory\PayloadDecoder::class),
            app(\App\Services\Inventory\AssetMatcher::class),
            app(\App\Services\Inventory\Tree\TreeWriter::class),
        );
    }

    private function ram(string $slot, string $serial, int $mb = 8192): array
    {
        return ['numslots' => $slot, 'serialnumber' => $serial, 'capacity' => $mb, 'type' => 'DDR4', 'speed' => '3200'];
    }

    public function test_first_inventory_is_baseline_not_a_change(): void
    {
        $this->report([$this->ram('1', 'A')]);

        $this->assertSame(0, InvChange::count());
        $this->assertSame(1, DB::table('inv_memories')->where('asset_id', $this->asset->id)->count());
    }

    public function test_swapped_ram_goes_pending_and_tree_is_untouched(): void
    {
        $this->report([$this->ram('1', 'OLD')]);
        $this->report([$this->ram('1', 'NEW')]);

        $this->assertSame(2, InvChange::where('state', 'pending')->count()); // removed OLD + added NEW
        $serials = DB::table('inv_memories')->where('is_current', true)->pluck('serial')->all();
        $this->assertSame(['OLD'], $serials);

        // Gui lai cung bao cao -> khong sinh them yeu cau trung
        $this->report([$this->ram('1', 'NEW')]);
        $this->assertSame(2, InvChange::where('state', 'pending')->count());
    }

    public function test_approving_updates_tree_and_creates_removed_part_and_maintenance(): void
    {
        $this->report([$this->ram('1', 'OLD')]);
        $this->report([$this->ram('1', 'NEW')]);

        $applier = app(ChangeApplier::class);
        foreach (InvChange::where('state', 'pending')->get() as $c) {
            $applier->approve($c);
        }

        $this->assertSame(['NEW'], DB::table('inv_memories')->where('is_current', true)->pluck('serial')->all());
        $part = DB::table('inv_removed_parts')->where('asset_id', $this->asset->id)->first();
        $this->assertSame('OLD', $part->serial);
        $this->assertSame('hong', $part->condition);
        $this->assertNotNull($part->maintenance_id);
        $this->assertNotNull(Maintenance::find($part->maintenance_id));
        $this->assertSame(0, InvChange::where('state', 'pending')->count());
    }

    public function test_rejecting_leaves_tree_unchanged(): void
    {
        $this->report([$this->ram('1', 'OLD')]);
        $this->report([$this->ram('1', 'NEW')]);

        $applier = app(ChangeApplier::class);
        foreach (InvChange::where('state', 'pending')->get() as $c) {
            $applier->reject($c);
        }

        $this->assertSame(['OLD'], DB::table('inv_memories')->where('is_current', true)->pluck('serial')->all());
        $this->assertSame(0, DB::table('inv_removed_parts')->count());
    }

    public function test_ip_change_is_automatic_but_motherboard_serial_is_critical_pending(): void
    {
        $this->report([$this->ram('1', 'A')], ['msn' => 'MB-1', 'mmodel' => 'H610']);
        $this->report([$this->ram('1', 'A')], ['msn' => 'MB-2', 'mmodel' => 'H610']);

        $c = InvChange::where('section', 'bios')->first();
        $this->assertSame('critical', $c->severity);
        $this->assertSame('MB-1', DB::table('inv_bios')->where('asset_id', $this->asset->id)->value('msn'));

        app(ChangeApplier::class)->approve($c);
        $this->assertSame('MB-2', DB::table('inv_bios')->where('asset_id', $this->asset->id)->value('msn'));
    }

    public function test_unknown_tag_is_pending_and_does_not_touch_location(): void
    {
        $this->report([$this->ram('1', 'A')]);

        $row = InvTagLocation::where('tag', 'PHONG-KE-TOAN')->first();
        $this->assertSame('pending', $row->state);
        $this->assertNull($this->asset->fresh()->location_id);
    }

    public function test_unassigned_tag_is_ignored(): void
    {
        $this->agent->update(['tag' => 'CHUA-PHAN-NHOM']);
        $this->report([$this->ram('1', 'A')]);

        $this->assertSame('ignored', InvTagLocation::where('tag', 'CHUA-PHAN-NHOM')->value('state'));
    }

    public function test_assigning_tag_batch_moves_all_machines_with_that_tag(): void
    {
        $this->report([$this->ram('1', 'A')]);
        $location = Location::factory()->create();

        $moved = app(TagLocationService::class)->assign(InvTagLocation::where('tag', 'PHONG-KE-TOAN')->first(), $location->id);

        $this->assertSame(1, $moved);
        $this->assertSame($location->id, $this->asset->fresh()->location_id);
    }

    public function test_approved_tag_with_different_location_creates_pending_change(): void
    {
        $this->report([$this->ram('1', 'A')]);
        $a = Location::factory()->create();
        $b = Location::factory()->create();
        $svc = app(TagLocationService::class);
        $svc->assign(InvTagLocation::where('tag', 'PHONG-KE-TOAN')->first(), $a->id);

        $this->asset->update(['location_id' => $b->id]);
        $this->report([$this->ram('1', 'A')]);

        $change = InvChange::where('section', 'khoa_phong')->first();
        $this->assertNotNull($change);
        $this->assertSame($b->id, $this->asset->fresh()->location_id); // chua duoc tu dong ghi de
        app(ChangeApplier::class)->approve($change);
        $this->assertSame($a->id, $this->asset->fresh()->location_id);
    }

    public function test_empty_location_is_filled_with_stock_only(): void
    {
        $stock = Location::factory()->create();
        config(['inventory.stock_location_id' => $stock->id]);

        $this->report([$this->ram('1', 'A')]);
        $this->assertSame($stock->id, $this->asset->fresh()->location_id);

        $other = Location::factory()->create();
        $this->asset->update(['location_id' => $other->id]);
        $this->report([$this->ram('1', 'A')]);
        $this->assertSame($other->id, $this->asset->fresh()->location_id);
    }
}
