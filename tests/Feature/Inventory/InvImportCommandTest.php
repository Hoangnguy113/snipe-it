<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use Tests\TestCase;

class InvImportCommandTest extends TestCase
{
    public function test_imports_an_inventory_json_file_without_an_agent(): void
    {
        Asset::factory()->create(['serial' => 'SN-IMPORT']);

        $file = tempnam(sys_get_temp_dir(), 'inv').'.json';
        file_put_contents($file, json_encode([
            'action' => 'inventory',
            'deviceid' => 'PC-IMPORT',
            'content' => [
                'hardware' => ['name' => 'PC-IMPORT'],
                'bios' => ['ssn' => 'SN-IMPORT'],
                'remote_mgmt' => [['id' => '99887766', 'type' => 'rustdesk']],
            ],
        ]));

        $this->artisan('inv:import', ['file' => $file])
            ->expectsOutputToContain('PC-IMPORT')
            ->assertExitCode(0);

        unlink($file);

        $agent = InvAgent::where('deviceid', 'PC-IMPORT')->first();

        $this->assertNotNull($agent);
        $this->assertSame('99887766', $agent->rustdesk_id);
        $this->assertSame(1, InvSnapshot::count());
    }

    public function test_fails_cleanly_when_the_file_does_not_exist(): void
    {
        $this->artisan('inv:import', ['file' => '/khong/co/file.json'])
            ->assertExitCode(1);
    }

    public function test_doctor_reports_on_the_core_files(): void
    {
        $this->artisan('inv:doctor')
            ->expectsOutputToContain('RouteServiceProvider')
            ->assertExitCode(0);
    }
}
