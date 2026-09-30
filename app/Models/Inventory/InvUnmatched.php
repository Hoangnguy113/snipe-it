<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvUnmatched extends Model
{
    use HasFactory;

    protected $table = 'inv_unmatched';

    protected $fillable = [
        'inv_agent_id', 'hostname', 'serial', 'machine_uuid', 'reason',
        'resolved_asset_id', 'resolved_by', 'resolved_at',
    ];

    protected $casts = [
        'resolved_asset_id' => 'integer',
        'resolved_by' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(InvAgent::class, 'inv_agent_id');
    }
}
