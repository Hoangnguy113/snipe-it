<?php

namespace App\Console\Commands\Inventory;

use App\Models\Inventory\InvHeartbeat;
use Illuminate\Console\Command;

class PruneHeartbeats extends Command
{
    protected $signature = 'inv:prune-heartbeats';

    protected $description = 'Xoá nhịp tim cũ hơn heartbeat_retention_days';

    public function handle(): int
    {
        $deleted = InvHeartbeat::where('received_at', '<', now()->subDays((int) config('inventory.heartbeat_retention_days')))->delete();
        $this->info("Đã xoá {$deleted} nhịp tim cũ.");

        return self::SUCCESS;
    }
}
