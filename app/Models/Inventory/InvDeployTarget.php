<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvDeployTarget extends Model
{
    protected $table = 'inv_deploy_targets';

    protected $guarded = [];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(InvDeployJob::class, 'inv_deploy_job_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(InvAgent::class, 'inv_agent_id');
    }
}
