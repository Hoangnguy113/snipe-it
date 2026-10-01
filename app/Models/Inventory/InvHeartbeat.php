<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InvHeartbeat extends Model
{
    public $timestamps = false;

    protected $table = 'inv_heartbeats';

    protected $guarded = [];

    protected $casts = [
        'received_at' => 'datetime',
        'disks' => 'array',
        'rustdesk_running' => 'boolean',
    ];

    /** Phần trăm trống thấp nhất trong các ổ đĩa (null nếu không có số liệu). */
    public function lowestFreePercent(): ?float
    {
        $min = null;
        foreach ($this->disks ?? [] as $d) {
            $total = (float) ($d['total_mb'] ?? 0);
            if ($total > 0) {
                $pct = (float) ($d['free_mb'] ?? 0) * 100 / $total;
                $min = $min === null ? $pct : min($min, $pct);
            }
        }

        return $min;
    }
}
