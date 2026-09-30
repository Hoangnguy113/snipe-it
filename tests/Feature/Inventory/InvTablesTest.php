<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvTablesTest extends TestCase
{
    public function test_can_register_an_agent_and_attach_a_snapshot(): void
    {
        $agent = InvAgent::create([
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'agent_uuid' => Str::uuid()->toString(),
            'hostname' => 'PC-045',
            'tag' => 'PHONG-KE-TOAN',
            'agent_version' => '1.20',
        ]);

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => 'noi-dung-gia-lap',
            'content_hash' => str_repeat('a', 64),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        $this->assertSame($agent->id, $snapshot->agent->id);
        $this->assertCount(1, $agent->refresh()->snapshots);
    }

    public function test_deviceid_is_unique(): void
    {
        InvAgent::create(['deviceid' => 'TRUNG-NHAU']);

        $this->expectException(QueryException::class);
        InvAgent::create(['deviceid' => 'TRUNG-NHAU']);
    }

    // A real inventory report weighs 1-3MB. Laravel's binary() produces a BLOB
    // (max 64KB), so the migration must widen it to LONGBLOB - this pins that.
    public function test_payload_column_can_hold_a_three_megabyte_snapshot(): void
    {
        $agent = InvAgent::create(['deviceid' => 'PC-TO']);

        $big = str_repeat('x', 3 * 1024 * 1024);

        InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => $big,
            'content_hash' => str_repeat('b', 64),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        $stored = DB::table('inv_snapshots')->where('inv_agent_id', $agent->id)->value('payload');
        $this->assertSame(strlen($big), strlen($stored));
    }

    public function test_factories_build_valid_rows(): void
    {
        $agent = InvAgent::factory()->create();

        $this->assertNotEmpty($agent->deviceid);
        $this->assertSame('active', $agent->refresh()->state);
    }
}
