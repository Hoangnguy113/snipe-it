<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InvTagLocation extends Model
{
    protected $table = 'inv_tag_locations';

    protected $guarded = [];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
