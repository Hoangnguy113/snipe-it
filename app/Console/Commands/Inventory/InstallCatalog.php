<?php

namespace App\Console\Commands\Inventory;

use App\Models\User;
use App\Services\Inventory\Catalog\CatalogInstaller;
use Illuminate\Console\Command;

class InstallCatalog extends Command
{
    protected $signature = 'inv:catalog-install {--user= : ID người dùng ghi là người tạo (mặc định: superuser đầu tiên)}';

    protected $description = 'Cài danh mục thiết bị ngang menu GLPI (spec mục 18) - chạy lại an toàn, chỉ thêm không xoá';

    public function handle(): int
    {
        $creator = $this->option('user')
            ? User::find($this->option('user'))
            : User::where('permissions->superuser', '1')->first();

        if (! $creator) {
            $this->error('Không tìm thấy người dùng để ghi làm người tạo. Tạo một superuser hoặc truyền --user=ID.');

            return self::FAILURE;
        }

        $result = (new CatalogInstaller(config('inventory_catalog')))->install($creator);

        foreach ($result as $what => $count) {
            $this->line("{$what}: {$count}");
        }
        $this->info('Xong. Chạy lại lệnh này không tạo trùng.');

        return self::SUCCESS;
    }
}
