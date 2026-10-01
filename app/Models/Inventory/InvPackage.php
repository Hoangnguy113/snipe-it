<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvPackage extends Model
{
    protected $table = 'inv_packages';

    protected $guarded = [];

    protected $casts = ['needs_reboot' => 'boolean', 'ask_user' => 'boolean'];

    /** file_path lưu đường dẫn tương đối trong storage/app để chuyển máy chủ không hỏng. */
    public function absolutePath(): string
    {
        return storage_path('app/'.$this->file_path);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(InvPackageCheck::class, 'inv_package_id');
    }
}
