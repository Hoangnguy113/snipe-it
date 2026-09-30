<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvUnmatched;
use App\Services\Inventory\AssetMatcher;
use Tests\TestCase;

class AssetMatcherTest extends TestCase
{
    private InvAgent $agent;

    private AssetMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = InvAgent::create(['deviceid' => 'PC-TEST']);
        $this->matcher = new AssetMatcher;
    }

    public function test_matches_by_serial_from_bios_ssn(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-ABC-123']);

        $id = $this->matcher->match(['bios' => ['ssn' => 'SN-ABC-123']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_serial_match_ignores_case_and_surrounding_spaces(): void
    {
        $asset = Asset::factory()->create(['serial' => 'SN-ABC-123']);

        $id = $this->matcher->match(['bios' => ['ssn' => '  sn-abc-123 ']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_falls_back_to_asset_tag_when_serial_does_not_match(): void
    {
        $asset = Asset::factory()->create(['serial' => 'OTHER', 'asset_tag' => 'TS-0045']);

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'NOT-IN-THE-REGISTER'],
            'hardware' => ['name' => 'TS-0045'],
        ], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    public function test_falls_back_to_asset_name(): void
    {
        $asset = Asset::factory()->create(['serial' => 'OTHER', 'name' => 'PC-KE-TOAN-02']);

        $id = $this->matcher->match(['hardware' => ['name' => 'PC-KE-TOAN-02']], $this->agent);

        $this->assertSame($asset->id, $id);
    }

    // RB-9: never auto-create an asset. An unknown machine on the network goes
    // to the register for manual assignment.
    public function test_records_unmatched_and_creates_no_asset(): void
    {
        $before = Asset::count();

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'UNKNOWN-999'],
            'hardware' => ['name' => 'UNKNOWN-PC', 'uuid' => 'unknown-uuid'],
        ], $this->agent);

        $this->assertNull($id);
        $this->assertSame($before, Asset::count());

        $row = InvUnmatched::where('inv_agent_id', $this->agent->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('UNKNOWN-999', $row->serial);
        $this->assertSame('UNKNOWN-PC', $row->hostname);
    }

    public function test_does_not_duplicate_unmatched_rows_for_the_same_agent(): void
    {
        $content = ['bios' => ['ssn' => 'UNKNOWN-999'], 'hardware' => ['name' => 'UNKNOWN-PC']];

        $this->matcher->match($content, $this->agent);
        $this->matcher->match($content, $this->agent);

        $this->assertSame(1, InvUnmatched::where('inv_agent_id', $this->agent->id)->count());
    }

    // RB-8: never match by RustDesk ID - it is duplicated or changes when a
    // machine is ghosted/cloned.
    public function test_never_matches_by_rustdesk_id(): void
    {
        Asset::factory()->create(['serial' => '16659046']);
        $this->agent->update(['rustdesk_id' => '16659046']);

        $id = $this->matcher->match([
            'remote_mgmt' => [['id' => '16659046', 'type' => 'rustdesk']],
        ], $this->agent);

        $this->assertNull($id);
    }

    public function test_prefers_serial_over_hostname_when_both_match_different_assets(): void
    {
        $bySerial = Asset::factory()->create(['serial' => 'SN-PRIORITY']);
        Asset::factory()->create(['serial' => 'SN-OTHER', 'name' => 'HOST-NAME']);

        $id = $this->matcher->match([
            'bios' => ['ssn' => 'SN-PRIORITY'],
            'hardware' => ['name' => 'HOST-NAME'],
        ], $this->agent);

        $this->assertSame($bySerial->id, $id);
    }
}
