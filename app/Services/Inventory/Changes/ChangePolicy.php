<?php

namespace App\Services\Inventory\Changes;

/**
 * Quy tắc tách đôi tự động ghi / chờ duyệt (spec §7).
 * Section không có ở đây = 🟢 tự động.
 */
class ChangePolicy
{
    /**
     * mode 'all'    : thêm/bớt/đổi đều chờ duyệt
     * mode 'keys'   : chỉ thêm/bớt chờ duyệt, đổi trường thì tự ghi (vd IP đổi theo DHCP)
     * mode 'fields' : chỉ các trường liệt kê chờ duyệt
     *
     * @return array<string, array{mode: string, fields?: array<string, string>}>
     */
    public static function rules(): array
    {
        return [
            'memories' => ['mode' => 'all'],
            'storages' => ['mode' => 'all'],
            'processors' => ['mode' => 'all'],
            'videos' => ['mode' => 'all'],
            'monitors' => ['mode' => 'all'],
            'networks' => ['mode' => 'keys'],
            'bios' => ['mode' => 'fields', 'fields' => ['msn' => 'critical', 'ssn' => 'critical', 'mmodel' => 'warning']],
        ];
    }

    public static function rule(string $section): ?array
    {
        return self::rules()[$section] ?? null;
    }
}
