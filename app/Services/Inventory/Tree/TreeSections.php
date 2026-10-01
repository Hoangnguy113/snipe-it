<?php

namespace App\Services\Inventory\Tree;

use App\Services\Inventory\SectionReader as R;
use Closure;

/**
 * Ánh xạ báo cáo kiểm kê -> 17 bảng cây linh kiện (spec 6.2).
 * Khoá JSON là CHỮ THƯỜNG nhưng vẫn đọc qua SectionReader (RB-4).
 */
class TreeSections
{
    /** @return array<string, string> section => bảng */
    public static function tables(): array
    {
        return [
            'hardware' => 'inv_hardware', 'bios' => 'inv_bios', 'processors' => 'inv_processors',
            'memories' => 'inv_memories', 'storages' => 'inv_storages', 'drives' => 'inv_drives',
            'networks' => 'inv_networks', 'videos' => 'inv_videos', 'sounds' => 'inv_sounds',
            'monitors' => 'inv_monitors', 'batteries' => 'inv_batteries', 'controllers' => 'inv_controllers',
            'slots' => 'inv_slots', 'ports' => 'inv_ports', 'softwares' => 'inv_softwares',
            'antivirus' => 'inv_antivirus', 'logged_users' => 'inv_logged_users',
        ];
    }

    /**
     * @param  array<string, mixed>  $c  nội dung `content` của báo cáo
     * @return array<string, list<array<string, mixed>>> section => các hàng (cột + part_key)
     */
    public static function map(array $c): array
    {
        return [
            'hardware' => self::hardware($c),
            'bios' => self::bios($c),
            'processors' => self::each($c, 'cpus', fn ($r) => [
                'name' => R::value($r, 'name'), 'manufacturer' => R::value($r, 'manufacturer'),
                'speed_mhz' => self::int(R::value($r, 'speed')), 'core_count' => self::int(R::value($r, 'core')),
                'thread_count' => self::int(R::value($r, 'thread')), 'family' => R::value($r, 'familynumber'),
                'stepping' => self::str(R::value($r, 'stepping')), 'serial' => R::value($r, 'serial'),
                'socket' => R::value($r, 'socket'),
            ], fn ($x) => ($x['socket'] ?? '').'|'.$x['name']),
            'memories' => self::each($c, 'memories', fn ($r) => [
                'slot_number' => self::str(R::value($r, 'numslots') ?? R::value($r, 'caption')),
                'capacity_mb' => self::int(R::value($r, 'capacity')), 'mem_type' => R::value($r, 'type'),
                'speed_mhz' => self::int(R::value($r, 'speed')), 'serial' => R::value($r, 'serialnumber'),
                'manufacturer' => R::value($r, 'manufacturer'), 'description' => R::value($r, 'description'),
                'removable' => self::bool(R::value($r, 'removable')),
            ], fn ($x) => $x['slot_number'].'|'.(self::filled($x['serial'])
                ? $x['serial']
                : $x['capacity_mb'].'|'.$x['mem_type'].'|'.$x['speed_mhz'])),
            'storages' => self::each($c, 'storages', fn ($r) => [
                'serial' => R::value($r, 'serial'), 'model' => R::value($r, 'model'),
                'manufacturer' => R::value($r, 'manufacturer'), 'disk_type' => R::value($r, 'type'),
                'size_mb' => self::int(R::value($r, 'disksize')), 'firmware' => R::value($r, 'firmware'),
                'interface' => R::value($r, 'interface'), 'wwn' => R::value($r, 'wwn'),
            ], fn ($x) => self::filled($x['serial']) ? $x['serial'] : $x['model'].'|'.$x['size_mb']),
            'drives' => self::each($c, 'drives', fn ($r) => [
                'letter' => R::value($r, 'letter'), 'label' => R::value($r, 'label'),
                'filesystem' => R::value($r, 'filesystem'), 'total_mb' => self::int(R::value($r, 'total')),
                'free_mb' => self::int(R::value($r, 'free')), 'is_system_drive' => self::bool(R::value($r, 'systemdrive')),
                'volume_serial' => R::value($r, 'serial'),
            ], fn ($x) => (string) ($x['letter'] ?? $x['label'])),
            'networks' => self::networks($c),
            'videos' => self::each($c, 'videos', fn ($r) => [
                'name' => R::value($r, 'name'), 'chipset' => R::value($r, 'chipset'),
                'memory_mb' => self::int(R::value($r, 'memory')), 'resolution' => R::value($r, 'resolution'),
                'driver_version' => R::value($r, 'driver'),
            ], fn ($x) => $x['name'].'|'.$x['memory_mb']),
            'sounds' => self::each($c, 'sounds', fn ($r) => [
                'name' => R::value($r, 'name'), 'manufacturer' => R::value($r, 'manufacturer'),
                'description' => R::value($r, 'description'),
            ], fn ($x) => (string) $x['name']),
            'monitors' => self::each($c, 'monitors', fn ($r) => [
                'caption' => R::value($r, 'caption'), 'manufacturer' => R::value($r, 'manufacturer'),
                'serial' => R::value($r, 'serial'), 'altserial' => R::value($r, 'altserial'),
                'description' => R::value($r, 'description'), 'manufacture_year' => null, 'size_inch' => null,
            ], fn ($x) => self::filled($x['serial']) ? $x['serial']
                : (self::filled($x['altserial']) ? $x['altserial'] : $x['manufacturer'].'|'.$x['caption'])),
            'batteries' => self::each($c, 'batteries', function ($r) {
                $cap = self::int(R::value($r, 'capacity'));
                $real = self::int(R::value($r, 'real_capacity'));

                return [
                    'name' => R::value($r, 'name'), 'manufacturer' => R::value($r, 'manufacturer'),
                    'serial' => R::value($r, 'serial'), 'capacity_mwh' => $cap, 'real_capacity_mwh' => $real,
                    'voltage_mv' => self::int(R::value($r, 'voltage')), 'manufacture_date' => R::value($r, 'date'),
                    'health_percent' => ($cap && $real !== null) ? (int) round($real * 100 / $cap) : null,
                ];
            }, fn ($x) => $x['serial'].'|'.$x['name']),
            'controllers' => self::each($c, 'controllers', fn ($r) => [
                'name' => R::value($r, 'name'), 'manufacturer' => R::value($r, 'manufacturer'),
                'controller_type' => R::value($r, 'type'), 'pci_id' => R::value($r, 'pcislot'),
                'driver_version' => R::value($r, 'driver'),
            ], fn ($x) => $x['pci_id'].'|'.$x['name']),
            'slots' => self::each($c, 'slots', fn ($r) => [
                'name' => R::value($r, 'name'), 'description' => R::value($r, 'description'),
                'designation' => R::value($r, 'designation'), 'status' => R::value($r, 'status'),
            ], fn ($x) => $x['name'].'|'.$x['designation']),
            'ports' => self::each($c, 'ports', fn ($r) => [
                'name' => R::value($r, 'name'), 'port_type' => R::value($r, 'type'),
                'description' => R::value($r, 'description'), 'caption' => R::value($r, 'caption'),
            ], fn ($x) => $x['name'].'|'.$x['port_type'].'|'.$x['caption']),
            'softwares' => self::each($c, 'softwares', fn ($r) => [
                'name' => R::value($r, 'name'), 'version' => R::value($r, 'version'),
                'publisher' => R::value($r, 'publisher'), 'install_date' => R::value($r, 'install_date'),
                'arch' => R::value($r, 'arch'), 'guid' => R::value($r, 'guid'),
                'uninstall_string' => R::value($r, 'uninstall_string'),
                'is_system_component' => strtolower((string) R::value($r, 'system_category')) !== 'application',
            ], fn ($x) => $x['name'].'|'.$x['version']),
            'antivirus' => self::each($c, 'antivirus', fn ($r) => [
                'name' => R::value($r, 'name'), 'company' => R::value($r, 'company'),
                'version' => R::value($r, 'version'), 'is_enabled' => self::bool(R::value($r, 'enabled')),
                'is_uptodate' => self::bool(R::value($r, 'uptodate')), 'expiration_date' => R::value($r, 'expiration'),
            ], fn ($x) => (string) $x['name']),
            'logged_users' => self::each($c, 'users', fn ($r) => [
                'login' => R::value($r, 'login'), 'domain' => R::value($r, 'domain'),
                'logged_at' => R::value(R::section($c, 'accesslog'), 'logdate'),
            ], fn ($x) => $x['domain'].'\\'.$x['login']),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function hardware(array $c): array
    {
        $hw = R::section($c, 'hardware');
        $os = R::section($c, 'operatingsystem');
        if ($hw === [] && $os === []) {
            return [];
        }

        return [[
            'part_key' => 'single',
            'hostname' => R::value($hw, 'name'), 'machine_uuid' => R::value($hw, 'uuid'),
            'domain' => R::value($os, 'dns_domain'), 'workgroup' => R::value($hw, 'workgroup'),
            'os_name' => R::value($os, 'full_name') ?? R::value($os, 'name'), 'os_version' => R::value($os, 'version'),
            'os_build' => R::value($os, 'kernel_version'), 'os_arch' => R::value($os, 'arch'),
            'os_install_date' => R::value($os, 'install_date'), 'memory_total_mb' => self::int(R::value($hw, 'memory')),
            'swap_mb' => self::int(R::value($hw, 'swap')), 'chassis_type' => R::value($hw, 'chassis_type'),
            'last_boot_at' => R::value($os, 'boot_time'), 'last_logged_user' => R::value($hw, 'lastloggeduser'),
        ]];
    }

    /** @return list<array<string, mixed>> */
    private static function bios(array $c): array
    {
        $b = R::section($c, 'bios');
        if ($b === []) {
            return [];
        }

        return [[
            'part_key' => 'single',
            'vendor' => R::value($b, 'bmanufacturer'), 'version' => R::value($b, 'bversion'),
            'bdate' => R::value($b, 'bdate'), 'ssn' => R::value($b, 'ssn'),
            'mmanufacturer' => R::value($b, 'mmanufacturer'), 'mmodel' => R::value($b, 'mmodel'),
            'msn' => R::value($b, 'msn'), 'biosserial' => R::value($b, 'biosserial'),
        ]];
    }

    /**
     * Agent gửi địa chỉ IPv4 và IPv6 của cùng 1 card thành 2 dòng: gộp theo MAC.
     *
     * @return list<array<string, mixed>>
     */
    private static function networks(array $c): array
    {
        $byMac = [];
        foreach (R::rows($c, 'networks') as $r) {
            $mac = strtoupper((string) R::value($r, 'mac'));
            if (! isset($byMac[$mac])) {
                $byMac[$mac] = [
                    'description' => R::value($r, 'description'), 'mac' => $mac ?: null, 'ipaddress' => null,
                    'ipmask' => null, 'ipgateway' => null, 'ipv6' => null, 'net_type' => R::value($r, 'type'),
                    'speed_mbps' => self::int(R::value($r, 'speed')), 'is_dhcp' => self::filled(R::value($r, 'ipdhcp')),
                    'is_virtual' => self::bool(R::value($r, 'virtualdev')),
                ];
            }
            $byMac[$mac]['ipaddress'] ??= R::value($r, 'ipaddress');
            $byMac[$mac]['ipmask'] ??= R::value($r, 'ipmask');
            $byMac[$mac]['ipgateway'] ??= R::value($r, 'ipgateway');
            $byMac[$mac]['ipv6'] ??= R::value($r, 'ipaddress6');
        }

        return self::keyed(array_values($byMac), fn ($x) => (string) ($x['mac'] ?? $x['description']));
    }

    /** @return list<array<string, mixed>> */
    private static function each(array $c, string $section, Closure $map, Closure $key): array
    {
        return self::keyed(array_map($map, R::rows($c, $section)), $key);
    }

    /**
     * Gán part_key; trùng khoá trong cùng 1 báo cáo thì thêm #n để không đè nhau.
     *
     * @return list<array<string, mixed>>
     */
    private static function keyed(array $rows, Closure $key): array
    {
        $seen = [];
        foreach ($rows as &$row) {
            $k = mb_substr((string) $key($row), 0, 180);
            $seen[$k] = ($seen[$k] ?? 0) + 1;
            $row['part_key'] = $seen[$k] > 1 ? $k.'#'.$seen[$k] : $k;
        }

        return $rows;
    }

    private static function int(mixed $v): ?int
    {
        return is_numeric($v) ? (int) $v : null;
    }

    private static function str(mixed $v): ?string
    {
        return is_scalar($v) ? (string) $v : null;
    }

    private static function bool(mixed $v): ?bool
    {
        return $v === null || $v === '' ? null : filter_var($v, FILTER_VALIDATE_BOOLEAN);
    }

    private static function filled(mixed $v): bool
    {
        return is_scalar($v) && trim((string) $v) !== '';
    }
}
