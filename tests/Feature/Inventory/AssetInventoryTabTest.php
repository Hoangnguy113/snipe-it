<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\User;
use App\Services\Inventory\Tree\TreeWriter;
use Tests\TestCase;

class AssetInventoryTabTest extends TestCase
{
    public function test_tab_shows_empty_state_without_inventory(): void
    {
        $asset = Asset::factory()->create();

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee(trans('admin/inventory/tree.tab'))
            ->assertSee(trans('admin/inventory/tree.empty'));
    }

    public function test_tab_lists_current_components_only(): void
    {
        $asset = Asset::factory()->create();
        $writer = new TreeWriter;
        $writer->sync('inv_hardware', $asset->id, 1, [['part_key' => 'single', 'hostname' => 'PC-TAB', 'os_name' => 'Windows 11']]);
        $writer->sync('inv_memories', $asset->id, 1, [
            ['part_key' => '1|OLD', 'slot_number' => '1', 'capacity_mb' => 4096, 'serial' => 'OLD-SERIAL'],
            ['part_key' => '2|NEW', 'slot_number' => '2', 'capacity_mb' => 16384, 'serial' => 'NEW-SERIAL'],
        ]);
        $writer->sync('inv_memories', $asset->id, 2, [
            ['part_key' => '2|NEW', 'slot_number' => '2', 'capacity_mb' => 16384, 'serial' => 'NEW-SERIAL'],
        ]);

        $this->actingAs(User::factory()->viewAssets()->create())
            ->get(route('hardware.show', $asset))
            ->assertOk()
            ->assertSee('PC-TAB')
            ->assertSee('NEW-SERIAL')
            ->assertDontSee('OLD-SERIAL');
    }
}
