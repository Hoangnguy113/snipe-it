<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Registry of the agents installed on workstations.
 *
 * Deliberately a plain Model rather than SnipeModel: this table is written by
 * machines, not edited through a form, so it needs no company scoping
 * (Companyable). It also skips Watson\Validating, whose save() returns false
 * silently on invalid data - that would swallow ingestion failures.
 */
class InvAgent extends Model
{
    use HasFactory;

    protected $table = 'inv_agents';

    protected $fillable = [
        'deviceid', 'agent_uuid', 'hostname', 'tag', 'asset_id',
        'agent_version', 'ip', 'rustdesk_id', 'rustdesk_version',
        'last_contact_at', 'last_inventory_at', 'last_heartbeat_at', 'state',
    ];

    protected $casts = [
        'asset_id' => 'integer',
        'last_contact_at' => 'datetime',
        'last_inventory_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
    ];

    public function latestHeartbeat(): HasOne
    {
        return $this->hasOne(InvHeartbeat::class, 'inv_agent_id')->latestOfMany('id');
    }

    public function isOnline(): bool
    {
        return $this->last_heartbeat_at !== null
            && $this->last_heartbeat_at->gte(now()->subMinutes((int) config('inventory.stale_heartbeat_minutes')));
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(InvSnapshot::class, 'inv_agent_id');
    }
}
