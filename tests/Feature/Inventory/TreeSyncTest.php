<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\Tree\TreeSections;
use App\Services\Inventory\Tree\TreeWriter;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TreeSyncTest extends TestCase
{
    private function content(): array
    {
        $json = json_decode(
            file_get_contents(base_path('tests/fixtures/inventory/Admin-PC-2026-09-30-18-09-03.json')),
            true
        );

        return $json['content'];
    }

    public function test_maps_real_fixture_into_tree_rows(): void
    {
        $m = TreeSections::map($this->content());

        $this->assertCount(2, $m['memories']);
        $this->assertSame('1', $m['memories'][0]['slot_number']);
        $this->assertSame(16384, $m['memories'][0]['capacity_mb']);
        $this->assertSame('002B547A', $m['memories'][0]['serial']);
        $this->assertSame('1|002B547A', $m['memories'][0]['part_key']);

        $this->assertSame('KD20250617A0300783', $m['storages'][0]['part_key']);
        $this->assertSame(512110, $m['storages'][0]['size_mb']);
        $this->assertSame('Microsoft Windows 11 Home Single Language', $m['hardware'][0]['os_name']);
        $this->assertSame(32491, $m['hardware'][0]['memory_total_mb']);
        $this->assertSame('PRO H610M-E (MS-7D48)', $m['bios'][0]['mmodel']);
        $this->assertCount(2, $m['drives']);
        $this->assertTrue($m['drives'][0]['is_system_drive']);
        $this->assertStringContainsString('i5-12400', $m['processors'][0]['name']);
        // 2 dong mang cung MAC (IPv4 + IPv6) gop thanh 1 card
        $this->assertCount(1, $m['networks']);
        $this->assertSame('192.168.33.65', $m['networks'][0]['ipaddress']);
        $this->assertNotEmpty($m['networks'][0]['ipv6']);
        $this->assertCount(132, $m['softwares']);
        // Khoa phai duy nhat de khong de len nhau
        $this->assertSame(count($m['ports']), count(array_unique(array_column($m['ports'], 'part_key'))));
    }

    public function test_battery_health_is_computed(): void
    {
        $m = TreeSections::map(['BATTERIES' => [['NAME' => 'B', 'SERIAL' => 'S', 'CAPACITY' => 50000, 'REAL_CAPACITY' => 20000]]]);

        $this->assertSame(40, $m['batteries'][0]['health_percent']);
    }

    public function test_writer_is_idempotent_and_flags_removed_rows(): void
    {
        $asset = Asset::factory()->create();
        $writer = new TreeWriter;
        $a = ['part_key' => '1|A', 'slot_number' => '1', 'capacity_mb' => 8192];
        $b = ['part_key' => '2|B', 'slot_number' => '2', 'capacity_mb' => 8192];

        $writer->sync('inv_memories', $asset->id, 1, [$a, $b]);
        $writer->sync('inv_memories', $asset->id, 2, [$a, $b]);
        $this->assertSame(2, DB::table('inv_memories')->where('asset_id', $asset->id)->count());

        $writer->sync('inv_memories', $asset->id, 3, [$a]);
        $this->assertSame(1, DB::table('inv_memories')->where('asset_id', $asset->id)->where('is_current', true)->count());
        $this->assertSame(0, (int) DB::table('inv_memories')->where('part_key', '2|B')->value('is_current'));
        $this->assertSame(3, (int) DB::table('inv_memories')->where('part_key', '1|A')->value('inv_snapshot_id'));
    }

    public function test_process_snapshot_fills_tree_for_matched_asset(): void
    {
        $content = $this->content();
        $asset = Asset::factory()->create(['asset_tag' => 'Admin-PC']);
        $agent = InvAgent::create(['deviceid' => 'Admin-PC-x', 'state' => 'active']);
        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress(json_encode(['action' => 'inventory', 'deviceid' => 'Admin-PC-x', 'content' => $content])),
            'content_hash' => 'h', 'protocol' => 'json', 'received_at' => now(),
        ]);

        (new \App\Jobs\Inventory\ProcessSnapshot($snapshot->id))->handle(
            app(\App\Services\Inventory\PayloadDecoder::class),
            app(\App\Services\Inventory\AssetMatcher::class),
            app(TreeWriter::class),
        );

        $this->assertSame($asset->id, $agent->fresh()->asset_id);
        $this->assertSame(2, DB::table('inv_memories')->where('asset_id', $asset->id)->count());
        $this->assertSame(1, DB::table('inv_hardware')->where('asset_id', $asset->id)->count());
    }

    public function test_unmatched_machine_gets_no_tree(): void
    {
        $agent = InvAgent::create(['deviceid' => 'Stranger-x', 'state' => 'active']);
        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress(json_encode(['action' => 'inventory', 'deviceid' => 'Stranger-x', 'content' => $this->content()])),
            'content_hash' => 'h2', 'protocol' => 'json', 'received_at' => now(),
        ]);

        (new \App\Jobs\Inventory\ProcessSnapshot($snapshot->id))->handle(
            app(\App\Services\Inventory\PayloadDecoder::class),
            app(\App\Services\Inventory\AssetMatcher::class),
            app(TreeWriter::class),
        );

        $this->assertSame(0, DB::table('inv_memories')->count());
    }
}
