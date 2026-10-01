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
