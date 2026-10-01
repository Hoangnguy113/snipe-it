<?php

namespace App\Services\Inventory\Reports;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Điểm mở rộng của "Điều chỉnh báo cáo tài sản" (spec §11.5): file lõi chỉ gọi
 * appendHeader()/appendRow(); toàn bộ logic ở đây. Dữ liệu nạp theo lô 1 lần (không N+1).
 */
class ColumnExtension
{
    /** khoá checkbox => nhãn cột (thứ tự cố định) */
    public const COLUMNS = [
        'inv_cpu' => 'CPU',
        'inv_ram' => 'RAM (MB) / khe đã dùng',
        'inv_disks' => 'Ổ cứng (model/serial/dung lượng)',
        'inv_free_pct' => '% đĩa còn trống',
        'inv_os' => 'Hệ điều hành',
        'inv_net' => 'MAC / IP',
        'inv_rustdesk' => 'RustDesk ID',
        'inv_last_agent' => 'Lần agent báo cuối',
        'inv_agent_state' => 'Trạng thái agent',
    ];

    /** @var array<string, array<int, mixed>>|null */
    private static ?array $cache = null;

    /** @param array<int, string> $header */
    public static function appendHeader(array &$header, Request $request): void
    {
        foreach (self::COLUMNS as $key => $label) {
            if ($request->filled($key)) {
                $header[] = $label;
            }
        }
    }

    /** @param array<int, mixed> $row */
    public static function appendRow(array &$row, Asset $asset, Request $request): void
    {
        $wanted = array_filter(array_keys(self::COLUMNS), fn ($k) => $request->filled($k));
        if ($wanted === []) {
            return;
        }

        $data = self::load();
        $id = $asset->id;

        foreach ($wanted as $key) {
            $row[] = $data[$key][$id] ?? '';
        }
    }

    public static function reset(): void
    {
        self::$cache = null;
    }

    /** @return array<string, array<int, mixed>> */
    private static function load(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $cpu = DB::table('inv_processors')->where('is_current', true)->get()->groupBy('asset_id')
            ->map(fn ($g) => $g->pluck('name')->implode(', '))->all();
        $hw = DB::table('inv_hardware')->where('is_current', true)->get()->keyBy('asset_id');
        $slots = DB::table('inv_memories')->where('is_current', true)->select('asset_id', DB::raw('COUNT(*) as n'))->groupBy('asset_id')->pluck('n', 'asset_id');
        $disks = DB::table('inv_storages')->where('is_current', true)->get()->groupBy('asset_id')
            ->map(fn ($g) => $g->map(fn ($d) => "{$d->model} / {$d->serial} / {$d->size_mb}MB")->implode('; '))->all();
        $free = DB::table('inv_drives')->where('is_current', true)->where('total_mb', '>', 0)
            ->select('asset_id', DB::raw('MIN(free_mb * 100.0 / total_mb) as pct'))->groupBy('asset_id')->pluck('pct', 'asset_id');
        $net = DB::table('inv_networks')->where('is_current', true)->get()->groupBy('asset_id')
            ->map(fn ($g) => $g->filter(fn ($n) => ! $n->is_virtual && $n->ipaddress)->map(fn ($n) => "{$n->mac} / {$n->ipaddress}")->implode('; '))->all();
        $agents = InvAgent::whereNotNull('asset_id')->get()->keyBy('asset_id');

        return self::$cache = [
            'inv_cpu' => $cpu,
            'inv_ram' => $hw->map(fn ($h, $assetId) => $h->memory_total_mb.' / '.($slots[$assetId] ?? 0))->all(),
            'inv_disks' => $disks,
            'inv_free_pct' => $free->map(fn ($p) => round($p).'%')->all(),
            'inv_os' => $hw->map(fn ($h) => trim($h->os_name.' '.$h->os_version))->all(),
            'inv_net' => $net,
            'inv_rustdesk' => $agents->map(fn ($a) => $a->rustdesk_id)->all(),
            'inv_last_agent' => $agents->map(fn ($a) => (string) $a->last_inventory_at)->all(),
            'inv_agent_state' => $agents->map(fn ($a) => $a->state)->all(),
        ];
    }

    /**
     * Số máy THỰC CÀI cho từng giấy phép (đếm từ inv_softwares), nạp 1 lần.
     *
     * @return array<int, int> license_id => số máy
     */
    public static function installedPerLicense(): array
    {
        static $counts = null;
        if ($counts !== null) {
            return $counts;
        }

        $software = DB::table('inv_softwares')->where('is_current', true)
            ->select(DB::raw('LOWER(name) as lname'), DB::raw('COUNT(DISTINCT asset_id) as n'))->groupBy('lname')->get();

        $counts = [];
        foreach (DB::table('licenses')->whereNull('deleted_at')->get(['id', 'name']) as $l) {
            $needle = mb_strtolower((string) $l->name);
            $counts[$l->id] = $needle === '' ? 0 : (int) $software->filter(fn ($s) => str_contains($s->lname, $needle))->sum('n');
        }

        return $counts;
    }

    /** @return array<int, string> asset_id => lần agent báo cuối (cho báo cáo kiểm kê) */
    public static function lastAgentByAsset(): array
    {
        return InvAgent::whereNotNull('asset_id')->whereNotNull('last_inventory_at')->get()
            ->mapWithKeys(fn ($a) => [$a->asset_id => $a->last_inventory_at->format('Y-m-d H:i')])->all();
    }
}
