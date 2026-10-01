<?php

namespace App\Models\Inventory;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvRemoteSession extends Model
{
    protected $table = 'inv_remote_sessions';

    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
