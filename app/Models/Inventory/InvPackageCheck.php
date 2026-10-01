<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class InvPackageCheck extends Model
{
    public $timestamps = false;

    protected $table = 'inv_package_checks';

    protected $guarded = [];
}
