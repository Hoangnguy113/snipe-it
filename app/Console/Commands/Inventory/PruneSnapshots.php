<?php

namespace App\Console\Commands\Inventory;

use App\Models\Inventory\InvSnapshot;
use Illuminate\Console\Command;

class PruneSnapshots extends Command
{
    protected $signature = 'inv:prune-snapshots';

    protected $description = 'Giữ tối đa snapshot_retention_count bản kiểm kê/máy (luôn giữ bản mới nhất)';

    public function handle(): int
    {
        $keep = max(1, (int) config('inventory.snapshot_retention_count'));
        $deleted = 0;

        InvSnapshot::select('inv_agent_id')->distinct()->pluck('inv_agent_id')->each(function ($agentId) use ($keep, &$deleted) {
            $keepIds = InvSnapshot::where('inv_agent_id', $agentId)->orderByDesc('id')->limit($keep)->pluck('id');
            $deleted += InvSnapshot::where('inv_agent_id', $agentId)->whereNotIn('id', $keepIds)->delete();
        });

        $this->info("Đã xoá {$deleted} bản kiểm kê cũ.");

        return self::SUCCESS;
    }
}
