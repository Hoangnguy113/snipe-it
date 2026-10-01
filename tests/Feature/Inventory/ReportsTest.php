<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvChange;
use App\Models\License;
use App\Models\User;
use App\Services\Inventory\Reports\ColumnExtension;
use App\Services\Inventory\Reports\InventoryReports;
use App\Services\Inventory\Tree\TreeWriter;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    private function reportUser(): User
    {
        return User::factory()->create(['permissions' => json_encode(['reports.view' => '1'])]);
    }

    public function test_four_reports_render_and_export(): void
    {
        $user = $this->reportUser();
        foreach (InventoryReports::KEYS as $key) {
            $this->actingAs($user)->get(route('inventory.reports', $key))->assertOk();
            $this->actingAs($user)->get(route('inventory.reports', [$key, 'export' => 'csv']))
                ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->actingAs($user)->get(route('inventory.reports', [$key, 'export' => 'pdf']))
                ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
    }

    public function test_user_without_report_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get(route('inventory.reports', 'health'))->assertForbidden();
    }

    public function test_health_report_flags_low_disk_worn_battery_and_old_os(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'PC-H']);
        $w = new TreeWriter;
        $w->sync('inv_hardware', $asset->id, 1, [['part_key' => 'single', 'hostname' => 'PC-H', 'os_name' => 'Windows 10 Pro', 'memory_total_mb' => 8192]]);
        $w->sync('inv_drives', $asset->id, 1, [['part_key' => 'C:', 'letter' => 'C:', 'total_mb' => 1000, 'free_mb' => 50]]);
        $w->sync('inv_batteries', $asset->id, 1, [['part_key' => 'b', 'name' => 'b', 'health_percent' => 40]]);

        $rows = (new InventoryReports)->build('health')['rows'];

        $this->assertStringContainsString('Sắp hết đĩa', $rows[0][8]);
        $this->assertStringContainsString('Pin chai', $rows[0][8]);
        $this->assertStringContainsString('HĐH cũ', $rows[0][8]);
    }

    public function test_software_report_marks_software_without_license(): void
    {
        $asset = Asset::factory()->create();
        License::factory()->create(['name' => 'Microsoft Office']);
        (new TreeWriter)->sync('inv_softwares', $asset->id, 1, [
            ['part_key' => 'a', 'name' => 'Microsoft Office Professional', 'version' => '16', 'is_system_component' => false],
            ['part_key' => 'b', 'name' => 'Shady Tool', 'version' => '1', 'is_system_component' => false],
        ]);

        $rows = collect((new InventoryReports)->build('software')['rows'])->keyBy(0);

        $this->assertSame('Có', $rows['Microsoft Office Professional'][4]);
        $this->assertSame('KHÔNG CÓ BẢN QUYỀN', $rows['Shady Tool'][4]);
    }

    public function test_lost_report_lists_never_seen_and_stale_machines(): void
    {
        $never = Asset::factory()->create(['asset_tag' => 'NEVER-1']);
        $stale = Asset::factory()->create(['asset_tag' => 'STALE-1']);
        InvAgent::create(['deviceid' => 'S', 'state' => 'active', 'asset_id' => $stale->id, 'last_inventory_at' => now()->subDays(9)]);

        $types = collect((new InventoryReports)->build('lost')['rows'])->groupBy(1)->map(fn ($g) => $g->pluck(0)->all());

        $this->assertContains('Chưa bao giờ thấy agent', $types['NEVER-1']);
        $this->assertContains('Mất liên lạc', $types['STALE-1']);
    }

    public function test_changes_report_shows_approved_change(): void
    {
        $asset = Asset::factory()->create(['asset_tag' => 'CHG-1']);
        InvChange::create(['asset_id' => $asset->id, 'section' => 'memories', 'part_key' => 'k', 'change_type' => 'removed', 'old_value' => 'OLD', 'state' => 'approved', 'approved_at' => now()]);

        $rows = (new InventoryReports)->build('changes')['rows'];

        $this->assertSame('CHG-1', $rows[0][1]);
        $this->assertSame('OLD', $rows[0][5]);
    }

    public function test_custom_report_columns_are_appended_in_order(): void
    {
        ColumnExtension::reset();
        $asset = Asset::factory()->create();
        InvAgent::create(['deviceid' => 'X', 'state' => 'active', 'asset_id' => $asset->id, 'rustdesk_id' => '777', 'last_inventory_at' => now()]);
        (new TreeWriter)->sync('inv_processors', $asset->id, 1, [['part_key' => 'p', 'name' => 'Core i5']]);

        $request = Request::create('/', 'POST', ['inv_cpu' => '1', 'inv_rustdesk' => '1']);
        $header = ['Tag'];
        ColumnExtension::appendHeader($header, $request);
        $row = ['T1'];
        ColumnExtension::appendRow($row, $asset, $request);

        $this->assertSame(['Tag', 'CPU', 'RustDesk ID'], $header);
        $this->assertSame(['T1', 'Core i5', '777'], $row);
        ColumnExtension::reset();
    }

    public function test_license_audit_asset_and_index_pages_include_inventory_columns(): void
    {
        $admin = User::factory()->superuser()->create();
        License::factory()->create(['name' => 'Microsoft Office', 'seats' => 1]);

        $this->actingAs($admin)->get('reports/licenses')->assertOk()->assertSee('Số máy thực cài');
        $this->actingAs($admin)->get('reports/custom')->assertOk()->assertSee('inv_cpu', false);
        $this->actingAs($admin)->get(route('reports.audit'))->assertOk()->assertSee('Lần agent báo cuối');
        $this->actingAs($admin)->get(route('reports.index'))->assertOk()->assertSee(trans('admin/inventory/reports.health'));
    }
}
