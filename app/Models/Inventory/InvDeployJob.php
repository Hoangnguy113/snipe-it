<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvDeployJob extends Model
{
    protected $table = 'inv_deploy_jobs';

    protected $guarded = [];

    protected $casts = ['scope_ids' => 'array', 'expires_at' => 'datetime'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(InvPackage::class, 'inv_package_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(InvDeployTarget::class, 'inv_deploy_job_id');
    }
}
