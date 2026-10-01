<?php

namespace App\Models\Inventory;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvRemovedPart extends Model
{
    protected $table = 'inv_removed_parts';

    protected $guarded = [];

    protected $casts = [
        'specs' => 'array',
        'detected_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
