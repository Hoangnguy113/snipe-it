<?php

namespace Database\Seeders;

use App\Models\Statuslabel;
use App\Models\User;
use Illuminate\Database\Seeder;

class StatuslabelSeeder extends Seeder
{
    public function run()
    {
        Statuslabel::truncate();

        $admin = User::where('permissions->superuser', '1')->first() ?? User::factory()->firstAdmin()->create();

        Statuslabel::factory()->rtd()->create([
            'name' => 'Sẵn sàng cấp phát',
            'created_by' => $admin->id,
            'default_label' => 1,
            'notes' => 'Thiết bị hoạt động tốt trong kho',
        ]);

        Statuslabel::factory()->pending()->create([
            'name' => 'Đang chờ xử lý / Cài đặt',
            'created_by' => $admin->id,
            'notes' => 'Đang cài phần mềm hoặc kiểm tra kỹ thuật',
        ]);

        Statuslabel::factory()->archived()->create([
            'name' => 'Đã thanh lý / Hỏng nặng',
            'created_by' => $admin->id,
            'notes' => 'Không còn sử dụng được, đã thanh lý',
        ]);

        Statuslabel::factory()->outForRepair()->create([
            'name' => 'Đang sửa chữa / Bảo hành',
            'created_by' => $admin->id,
            'notes' => 'Đang gửi hãng hoặc trung tâm sửa chữa',
        ]);
    }
}
