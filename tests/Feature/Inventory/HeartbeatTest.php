<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvHeartbeat;
use App\Models\Inventory\InvSnapshot;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class HeartbeatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create();
        config(['inventory.agent_user' => 'qlts-agent', 'inventory.agent_secret' => 'test-secret']);
    }

    private function beat(array $body): TestResponse
    {
        return $this->call('POST', '/agent/heartbeat', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('qlts-agent:test-secret'),
            'HTTP_GLPI_AGENT_ID' => Str::uuid()->toString(),
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($body));
    }

    public function test_heartbeat_is_stored_and_agent_becomes_online(): void
    {
        $this->beat([
            'deviceid' => 'PC-HB', 'cpu_percent' => 12.5, 'ram_percent' => 60, 'logged_user' => 'CMS',
            'ip' => '10.0.0.9', 'rustdesk_running' => true, 'uptime_sec' => 3600,
            'disks' => [['letter' => 'C:', 'total_mb' => 100000, 'free_mb' => 5000]],
        ])->assertOk()->assertJsonPath('status', 'ok');

        $agent = InvAgent::where('deviceid', 'PC-HB')->first();
        $this->assertTrue($agent->isOnline());
        $hb = $agent->latestHeartbeat;
        $this->assertSame('CMS', $hb->logged_user);
        $this->assertEquals(5.0, round($hb->lowestFreePercent(), 1));
    }

    public function test_agent_goes_offline_after_stale_minutes(): void
    {
        $agent = InvAgent::create(['deviceid' => 'PC-OFF', 'state' => 'active', 'last_heartbeat_at' => now()->subMinutes(16)]);
        $this->assertFalse($agent->isOnline());

        $agent->update(['last_heartbeat_at' => now()->subMinutes(14)]);
        $this->assertTrue($agent->fresh()->isOnline());
    }

    public function test_missing_deviceid_is_rejected(): void
    {
        $this->beat(['cpu_percent' => 1])->assertStatus(400);
    }

    public function test_status_board_shows_state_and_requires_permission(): void
    {
        InvAgent::create(['deviceid' => 'PC-ON', 'hostname' => 'PC-ON', 'state' => 'active', 'last_heartbeat_at' => now()]);
        InvAgent::create(['deviceid' => 'PC-DEAD', 'hostname' => 'PC-DEAD', 'state' => 'active', 'last_heartbeat_at' => now()->subHour()]);

        $this->actingAs(User::factory()->create())->get(route('inventory.status'))->assertForbidden();

        $this->actingAs(User::factory()->create(['permissions' => json_encode(['inventory.view' => '1'])]))
            ->get(route('inventory.status'))->assertOk()->assertSee('PC-ON')->assertSee('PC-DEAD')->assertSee('1 / 2');
    }

    public function test_prune_heartbeats_and_snapshots(): void
    {
        $agent = InvAgent::create(['deviceid' => 'PC-P', 'state' => 'active']);
        InvHeartbeat::create(['inv_agent_id' => $agent->id, 'received_at' => now()->subDays(40)]);
        InvHeartbeat::create(['inv_agent_id' => $agent->id, 'received_at' => now()->subDays(1)]);
        for ($i = 0; $i < 13; $i++) {
            InvSnapshot::create(['inv_agent_id' => $agent->id, 'payload' => 'x', 'content_hash' => "h$i", 'protocol' => 'json', 'received_at' => now()]);
        }

        $this->artisan('inv:prune-heartbeats')->assertSuccessful();
        $this->artisan('inv:prune-snapshots')->assertSuccessful();

        $this->assertSame(1, InvHeartbeat::count());
        $this->assertSame(10, InvSnapshot::count());
        $this->assertSame('h12', InvSnapshot::orderByDesc('id')->value('content_hash'));
    }

    public function test_mark_stale_flags_silent_agents_and_revives_returning_ones(): void
    {
        $old = InvAgent::create(['deviceid' => 'OLD', 'state' => 'active', 'last_contact_at' => now()->subDays(8)]);
        $back = InvAgent::create(['deviceid' => 'BACK', 'state' => 'stale', 'last_contact_at' => now()->subHour()]);

        $this->artisan('inv:mark-stale')->assertSuccessful();

        $this->assertSame('stale', $old->fresh()->state);
        $this->assertSame('active', $back->fresh()->state);
    }
}
