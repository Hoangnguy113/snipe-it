<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IngestInventoryTest extends TestCase
{
    private string $agentUuid;

    protected function setUp(): void
    {
        parent::setUp();

        // CheckForSetup redirects to /setup until a user exists.
        User::factory()->create();

        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'test-secret',
        ]);
        $this->agentUuid = Str::uuid()->toString();
    }

    private function send(array $message): TestResponse
    {
        return $this->call('POST', '/agent/inventory', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('qlts-agent:test-secret'),
            'HTTP_GLPI_AGENT_ID' => $this->agentUuid,
            'CONTENT_TYPE' => 'application/x-compress-zlib',
        ], gzcompress(json_encode($message)));
    }

    private function inventoryMessage(array $overrides = []): array
    {
        return array_replace_recursive([
            'action' => 'inventory',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'content' => [
                'hardware' => ['name' => 'PC-045', 'uuid' => 'uuid-045'],
                'bios' => ['ssn' => 'SN-045', 'mmodel' => 'OptiPlex 7090'],
                'remote_mgmt' => [['id' => '16659046', 'type' => 'rustdesk']],
            ],
        ], $overrides);
    }

    public function test_inventory_is_stored_and_acknowledged(): void
    {
        $this->send($this->inventoryMessage())
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertSame(1, InvSnapshot::count());
    }

    public function test_snapshot_keeps_the_raw_compressed_payload(): void
    {
        $this->send($this->inventoryMessage())->assertOk();

        $snapshot = InvSnapshot::first();

        $this->assertSame('json', $snapshot->protocol);
        $this->assertSame(64, strlen($snapshot->content_hash));
        $this->assertNotEmpty($snapshot->payload);
    }

    public function test_inventory_links_the_agent_to_the_matching_asset(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertSame($asset->id, $agent->asset_id);
        $this->assertNotNull($agent->last_inventory_at);
    }

    // This is why RustDesk.pm had to be patched in GD0: the ID is GD4's input.
    public function test_inventory_stores_the_rustdesk_id(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertSame('16659046', $agent->rustdesk_id);
    }

    public function test_inventory_without_remote_mgmt_leaves_rustdesk_id_null(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $message = $this->inventoryMessage();
        unset($message['content']['remote_mgmt']);

        $this->send($message)->assertOk();

        $this->assertNull(
            InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->value('rustdesk_id')
        );
    }

    public function test_identical_inventory_sent_twice_stores_only_one_snapshot(): void
    {
        $message = $this->inventoryMessage();

        $this->send($message)->assertOk();
        $this->send($message)->assertOk();

        $this->assertSame(1, InvSnapshot::count());
    }

    // Dedupe compares against the LATEST snapshot only. A machine that goes
    // A -> B -> A must record the second A, or change detection (GD3) would
    // compare against a stale baseline.
    public function test_a_change_that_reverts_is_recorded_as_a_new_snapshot(): void
    {
        $a = $this->inventoryMessage();
        $b = $this->inventoryMessage(['content' => ['hardware' => ['name' => 'PC-045-RENAMED']]]);

        $this->send($a)->assertOk();
        $this->send($b)->assertOk();
        $this->send($a)->assertOk();

        $this->assertSame(3, InvSnapshot::count());
    }

    public function test_snapshot_is_marked_processed(): void
    {
        Asset::factory()->create(['serial' => 'SN-045']);

        $this->send($this->inventoryMessage())->assertOk();

        // The test environment uses QUEUE_CONNECTION=sync, so the job runs at once.
        $this->assertNotNull(InvSnapshot::first()->processed_at);
    }

    // A REAL inventory from a real machine (GD0 Task 3 step 5). This catches
    // every wrong assumption about the real data shape.
    public function test_accepts_the_real_inventory_fixture(): void
    {
        $files = glob(base_path('tests/fixtures/inventory/*.json'));

        if ($files === false || $files === []) {
            $this->markTestSkipped('No real fixture yet - run GD0 Task 3 step 5 first.');
        }

        $real = json_decode(file_get_contents($files[0]), true);
        $this->assertIsArray($real, 'Fixture is not valid JSON');

        $this->send($real)->assertOk();

        $this->assertSame(1, InvSnapshot::count());
    }
}
