<?php

namespace App\Services\Inventory\Tree;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Đọc cây linh kiện HIỆN TẠI của 1 tài sản để hiển thị.
 */
class TreeReader
{
    /**
     * Mục hiển thị: section => cột hiển thị (nhãn lấy từ admin/inventory/tree.columns.<cột>).
     *
     * @return array<string, list<string>>
     */
    public static function layout(): array
    {
        return [
            'hardware' => ['hostname', 'os_name', 'os_version', 'os_arch', 'memory_total_mb', 'domain', 'workgroup', 'last_boot_at', 'last_logged_user'],
            'bios' => ['mmanufacturer', 'mmodel', 'msn', 'ssn', 'vendor', 'version', 'bdate'],
            'processors' => ['name', 'core_count', 'thread_count', 'speed_mhz', 'socket'],
            'memories' => ['slot_number', 'capacity_mb', 'mem_type', 'speed_mhz', 'manufacturer', 'serial'],
            'storages' => ['model', 'disk_type', 'size_mb', 'serial', 'firmware', 'interface'],
            'drives' => ['letter', 'label', 'filesystem', 'total_mb', 'free_mb'],
            'networks' => ['description', 'mac', 'ipaddress', 'ipv6', 'ipgateway', 'speed_mbps'],
            'videos' => ['name', 'memory_mb', 'resolution'],
            'monitors' => ['caption', 'manufacturer', 'serial'],
            'sounds' => ['name', 'manufacturer'],
            'batteries' => ['name', 'serial', 'capacity_mwh', 'real_capacity_mwh', 'health_percent'],
            'antivirus' => ['name', 'company', 'version', 'is_enabled', 'is_uptodate'],
            'controllers' => ['name', 'manufacturer', 'controller_type'],
            'slots' => ['name', 'description', 'status'],
            'ports' => ['name', 'port_type', 'caption'],
            'logged_users' => ['login', 'domain', 'logged_at'],
            'softwares' => ['name', 'version', 'publisher', 'install_date'],
        ];
    }

    /**
     * @return array<string, Collection<int, object>> section => các hàng hiện tại (rỗng nếu chưa có)
     */
    public function forAsset(int $assetId): array
    {
        $tables = TreeSections::tables();
        $out = [];

        foreach (self::layout() as $section => $columns) {
            $query = DB::table($tables[$section])->where('asset_id', $assetId)->where('is_current', true);

            $out[$section] = $section === 'softwares'
                ? $query->where('is_system_component', false)->orderBy('name')->get()
                : $query->orderBy('id')->get();
        }

        return $out;
    }

    public function lastSeenAt(int $assetId): ?string
    {
        return DB::table('inv_hardware')->where('asset_id', $assetId)->where('is_current', true)->value('last_seen_at');
    }
}
