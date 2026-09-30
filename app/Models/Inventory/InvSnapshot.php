<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvSnapshot extends Model
{
    use HasFactory;

    protected $table = 'inv_snapshots';

    protected $fillable = [
        'inv_agent_id', 'asset_id', 'payload', 'content_hash',
        'protocol', 'received_at', 'processed_at', 'error',
    ];

    protected $casts = [
        'asset_id' => 'integer',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(InvAgent::class, 'inv_agent_id');
    }
}
