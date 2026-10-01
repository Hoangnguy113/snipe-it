<?php

namespace App\Console\Commands\Inventory;

use Illuminate\Console\Command;

/**
 * Checks that the patches on core files are intact after a Snipe-IT upgrade.
 *
 * Run after every Snipe-IT update. See spec section 13.
 */
class InventoryDoctor extends Command
{
    protected $signature = 'inv:doctor';

    protected $description = 'Check that patches on Snipe-IT core files are still in place';

    /**
     * path => [marker to look for, phase, what to do if missing]
     */
    private const PATCHES = [
        'app/Providers/RouteServiceProvider.php' => [
            'mapAgentRoutes',
            'GD1',
            'Add $this->mapAgentRoutes() to boot() and the mapAgentRoutes() method',
        ],
        'resources/views/hardware/view.blade.php' => [
            'inventory.asset-tree',
            'GD2',
            "Add the nav-item name=\"inventory\" and <x-tabs.pane name=\"inventory\"> with @include('inventory.asset-tree')",
        ],
        'app/Livewire/AlertMenu.php' => [
            'inv_changes',
            'GD3',
            'Add the pending InvChange/InvTagLocation counts to render() and to alert_count',
        ],
        'resources/views/livewire/alert-menu.blade.php' => [
            'inventory.approvals',
            'GD3',
            "Add the 'QLTS inventory approvals' header link block inside the alert_count > 0 branch",
        ],
        'config/permissions.php' => [
            'inventory.approve',
            'GD4',
            "Add the 'Inventory' permission group (inventory.view/approve, remote.control/deploy)",
        ],
        'app/Providers/AuthServiceProvider.php' => [
            'remote.control',
            'GD4',
            'Add the foreach Gate::define loop for the inventory/remote permissions',
        ],
        'app/Console/Kernel.php' => [
            'inv:mark-stale',
            'GD5',
            'Schedule inv:mark-stale (hourly), inv:prune-heartbeats and inv:prune-snapshots (daily)',
        ],
        'resources/views/reports/index.blade.php' => [
            'inventory.reports.links',
            'GD7',
            "Add @include('inventory.reports.links') at the start of the report-links row",
        ],
        'resources/views/reports/custom/asset.blade.php' => [
            'inventory.reports.asset-columns',
            'GD7',
            "Add @include('inventory.reports.asset-columns') after the 'url' checkbox",
        ],
        'app/Http/Controllers/ReportsController.php' => [
            'ColumnExtension::appendRow',
            'GD7',
            'Call ColumnExtension::appendHeader and appendRow in postCustom()',
        ],
        'resources/views/reports/licenses.blade.php' => [
            'inventory.reports.license-columns',
            'GD7',
            "Add @include('inventory.reports.license-columns') after the last th and the last td of each row",
        ],
        'resources/views/reports/audit.blade.php' => [
            'inventory.reports.audit-columns',
            'GD7',
            "Add @include('inventory.reports.audit-columns') after the notes th",
        ],
    ];

    public function handle(): int
    {
        $missing = 0;

        foreach (self::PATCHES as $path => [$needle, $phase, $todo]) {
            $full = base_path($path);

            if (! is_file($full)) {
                $this->error("[{$phase}] MISSING FILE  {$path}");
                $missing++;

                continue;
            }

            if (str_contains((string) file_get_contents($full), $needle)) {
                $this->info("[{$phase}] OK            {$path}");

                continue;
            }

            $this->error("[{$phase}] PATCH LOST    {$path}");
            $this->line("             To do: {$todo}");
            $missing++;
        }

        if ($missing > 0) {
            $this->newLine();
            $this->warn("{$missing} patch(es) must be re-applied. See docs/superpowers/specs/2026-09-30-tich-hop-tac-tu-qlts-design.md section 13.");
        }

        return self::SUCCESS;
    }
}
