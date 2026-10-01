<?php

namespace App\Models\Inventory;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvChange extends Model
{
    protected $table = 'inv_changes';

    protected $guarded = [];

    protected $casts = [
        'asset_id' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return array<string, mixed>|null */
    public function oldRow(): ?array
    {
        return $this->old_value === null ? null : json_decode($this->old_value, true);
    }

    /** @return array<string, mixed>|null */
    public function newRow(): ?array
    {
        return $this->new_value === null ? null : json_decode($this->new_value, true);
    }
}
