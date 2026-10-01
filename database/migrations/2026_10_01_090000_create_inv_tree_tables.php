<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 17 bảng cây linh kiện (spec 6.2). Kiểu cột: s=string, i=integer, b=bool,
 * t=text, f=decimal.
 */
return new class extends Migration
{
    private const TABLES = [
        'inv_hardware' => ['hostname' => 's', 'machine_uuid' => 's', 'domain' => 's', 'workgroup' => 's', 'os_name' => 's', 'os_version' => 's', 'os_build' => 's', 'os_arch' => 's', 'os_install_date' => 's', 'memory_total_mb' => 'i', 'swap_mb' => 'i', 'chassis_type' => 's', 'last_boot_at' => 's', 'last_logged_user' => 's'],
        'inv_bios' => ['vendor' => 's', 'version' => 's', 'bdate' => 's', 'ssn' => 's', 'mmanufacturer' => 's', 'mmodel' => 's', 'msn' => 's', 'biosserial' => 's'],
        'inv_processors' => ['name' => 's', 'manufacturer' => 's', 'speed_mhz' => 'i', 'core_count' => 'i', 'thread_count' => 'i', 'family' => 's', 'stepping' => 's', 'serial' => 's', 'socket' => 's'],
        'inv_memories' => ['slot_number' => 's', 'capacity_mb' => 'i', 'mem_type' => 's', 'speed_mhz' => 'i', 'serial' => 's', 'manufacturer' => 's', 'description' => 's', 'removable' => 'b'],
        'inv_storages' => ['serial' => 's', 'model' => 's', 'manufacturer' => 's', 'disk_type' => 's', 'size_mb' => 'i', 'firmware' => 's', 'interface' => 's', 'wwn' => 's'],
        'inv_drives' => ['letter' => 's', 'label' => 's', 'filesystem' => 's', 'total_mb' => 'i', 'free_mb' => 'i', 'is_system_drive' => 'b', 'volume_serial' => 's'],
        'inv_networks' => ['description' => 's', 'mac' => 's', 'ipaddress' => 's', 'ipmask' => 's', 'ipgateway' => 's', 'ipv6' => 's', 'net_type' => 's', 'speed_mbps' => 'i', 'is_dhcp' => 'b', 'is_virtual' => 'b'],
        'inv_videos' => ['name' => 's', 'chipset' => 's', 'memory_mb' => 'i', 'resolution' => 's', 'driver_version' => 's'],
        'inv_sounds' => ['name' => 's', 'manufacturer' => 's', 'description' => 's'],
        'inv_monitors' => ['caption' => 's', 'manufacturer' => 's', 'serial' => 's', 'altserial' => 's', 'description' => 's', 'manufacture_year' => 's', 'size_inch' => 'f'],
        'inv_batteries' => ['name' => 's', 'manufacturer' => 's', 'serial' => 's', 'capacity_mwh' => 'i', 'real_capacity_mwh' => 'i', 'voltage_mv' => 'i', 'manufacture_date' => 's', 'health_percent' => 'i'],
        'inv_controllers' => ['name' => 's', 'manufacturer' => 's', 'controller_type' => 's', 'pci_id' => 's', 'driver_version' => 's'],
        'inv_slots' => ['name' => 's', 'description' => 's', 'designation' => 's', 'status' => 's'],
        'inv_ports' => ['name' => 's', 'port_type' => 's', 'description' => 's', 'caption' => 's'],
        'inv_softwares' => ['name' => 's', 'version' => 's', 'publisher' => 's', 'install_date' => 's', 'arch' => 's', 'guid' => 's', 'uninstall_string' => 't', 'is_system_component' => 'b'],
        'inv_antivirus' => ['name' => 's', 'company' => 's', 'version' => 's', 'is_enabled' => 'b', 'is_uptodate' => 'b', 'expiration_date' => 's'],
        'inv_logged_users' => ['login' => 's', 'domain' => 's', 'logged_at' => 's'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                $table->unsignedInteger('asset_id')->index();
                $table->unsignedBigInteger('inv_snapshot_id')->nullable();
                $table->string('part_key', 191);
                foreach ($columns as $col => $type) {
                    match ($type) {
                        's' => $table->string($col)->nullable(),
                        'i' => $table->bigInteger($col)->nullable(),
                        'b' => $table->boolean($col)->nullable(),
                        't' => $table->text($col)->nullable(),
                        'f' => $table->decimal($col, 6, 1)->nullable(),
                    };
                }
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamp('last_seen_at')->nullable();
                $table->boolean('is_current')->default(true);
                $table->index(['asset_id', 'is_current']);
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABLES) as $name) {
            Schema::dropIfExists($name);
        }
    }
};
