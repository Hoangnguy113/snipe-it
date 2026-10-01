<?php

namespace App\Console\Commands\Inventory;

use App\Models\Inventory\InvAgent;
use Illuminate\Console\Command;

class MarkStaleAgents extends Command
{
    protected $signature = 'inv:mark-stale';

    protected $description = 'Đánh dấu agent mất liên lạc quá stale_inventory_days là stale (và đưa về active nếu đã liên lạc lại)';

    public function handle(): int
    {
        $cutoff = now()->subDays((int) config('inventory.stale_inventory_days'));
        $changed = 0;

        foreach (InvAgent::where('state', '!=', 'retired')->get() as $agent) {
            $last = collect([$agent->last_contact_at, $agent->last_inventory_at, $agent->last_heartbeat_at])->filter()->max();
            $state = ($last === null || $last->lt($cutoff)) ? 'stale' : 'active';

            if ($agent->state !== $state) {
                $agent->update(['state' => $state]);
                $changed++;
            }
        }

        $this->info("Đã cập nhật trạng thái {$changed} agent.");

        return self::SUCCESS;
    }
}
