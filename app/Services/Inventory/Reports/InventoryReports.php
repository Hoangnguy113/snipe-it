<?php

namespace App\Services\Inventory\Reports;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvUnmatched;
use App\Models\License;
use App\Models\Location;
use App\Models\User;
use App\Services\Inventory\Changes\TagLocationService;
use Illuminate\Support\Facades\DB;

/**
 * 4 báo cáo mới (spec §11.1). Mọi truy vấn gom theo lô (whereIn/groupBy), không N+1 (§11.5).
 *
 * @phpstan-type Report array{title: string, headers: list<string>, rows: list<list<mixed>>}
 */
class InventoryReports
{
    public const KEYS = ['changes', 'lost', 'software', 'health'];

    /**
     * @param  array<string, mixed>  $filters
     * @return Report
     */
    public function build(string $key, array $filters = []): array
    {
        return match ($key) {
            'changes' => $this->changes($filters),
            'lost' => $this->lost(),
            'software' => $this->software(),
            'health' => $this->health(),
        };
    }

    /** @return Report */
    private function changes(array $f): array
    {
        $q = InvChange::with('asset')->whereIn('state', ['approved', 'rejected', 'pending'])->orderByDesc('id');
        $q->when(! empty($f['from']), fn ($x) => $x->where('created_at', '>=', $f['from'].' 00:00:00'));
        $q->when(! empty($f['to']), fn ($x) => $x->where('created_at', '<=', $f['to'].' 23:59:59'));
        $q->when(! empty($f['section']), fn ($x) => $x->where('section', $f['section']));
        $q->when(! empty($f['location_id']), fn ($x) => $x->whereIn('asset_id', Asset::where('location_id', $f['location_id'])->select('id')));
        $changes = $q->get();

        $users = User::whereIn('id', $changes->pluck('approved_by')->filter())->pluck('username', 'id');
        $locations = Location::pluck('name', 'id');

        $rows = $changes->map(fn (InvChange $c) => [
            $c->created_at?->format('Y-m-d H:i'),
            $c->asset?->asset_tag,
            $locations[$c->asset?->location_id] ?? '',
            $c->section,
            $c->change_type,
            $this->short($c->old_value),
            $this->short($c->new_value),
            $c->state,
            $users[$c->approved_by] ?? '',
            $c->approved_at?->format('Y-m-d H:i'),
        ])->all();

        return ['title' => trans('admin/inventory/reports.changes'), 'headers' => ['Ngày', 'Tài sản', 'Khoa phòng', 'Mục', 'Loại', 'Cũ', 'Mới', 'Trạng thái', 'Người duyệt', 'Lúc duyệt'], 'rows' => $rows];
    }

    /** @return Report */
    private function lost(): array
    {
        $rows = [];
        $cutoff = now()->subDays((int) config('inventory.stale_inventory_days'));
        $agents = InvAgent::whereNotNull('asset_id')->get()->keyBy('asset_id');
        $assets = Asset::select('id', 'asset_tag', 'name', 'location_id')->get();

        foreach ($assets as $a) {
            $agent = $agents[$a->id] ?? null;
            if ($agent === null) {
                $rows[] = ['Chưa bao giờ thấy agent', $a->asset_tag, $a->name, '', ''];
            } elseif ($agent->last_inventory_at === null || $agent->last_inventory_at->lt($cutoff)) {
                $rows[] = ['Mất liên lạc', $a->asset_tag, $a->name, $agent->hostname, (string) $agent->last_inventory_at];
            }
        }

        foreach (InvUnmatched::whereNull('resolved_asset_id')->get() as $u) {
            $rows[] = ['Agent chưa khớp tài sản', '', $u->hostname, $u->serial, $u->reason];
        }

        $approved = DB::table('inv_tag_locations')->where('state', 'approved')->pluck('tag')->all();
        foreach (InvAgent::all() as $ag) {
            $tag = trim((string) $ag->tag);
            if ($tag === '' || $tag === TagLocationService::UNASSIGNED || ! in_array($tag, $approved, true)) {
                $rows[] = ['Chưa gán khoa phòng', $ag->asset_id ? ($assets->firstWhere('id', $ag->asset_id)?->asset_tag) : '', $ag->hostname, $tag, ''];
            }
        }

        return ['title' => trans('admin/inventory/reports.lost'), 'headers' => ['Loại', 'Tài sản', 'Tên máy', 'Chi tiết', 'Lần kiểm kê cuối / lý do'], 'rows' => $rows];
    }

    /** @return Report */
    private function software(): array
    {
        $rows = DB::table('inv_softwares')->where('is_current', true)->where('is_system_component', false)
            ->select('name', 'version', 'publisher', DB::raw('COUNT(DISTINCT asset_id) as machines'))
            ->groupBy('name', 'version', 'publisher')->orderBy('name')->get();

        $licenses = License::pluck('name')->map(fn ($n) => mb_strtolower((string) $n))->all();

        $out = $rows->map(function ($r) use ($licenses) {
            $name = mb_strtolower((string) $r->name);
            $licensed = collect($licenses)->contains(fn ($l) => $l !== '' && (str_contains($name, $l) || str_contains($l, $name)));

            return [$r->name, $r->version, $r->publisher, $r->machines, $licensed ? 'Có' : 'KHÔNG CÓ BẢN QUYỀN'];
        })->all();

        return ['title' => trans('admin/inventory/reports.software'), 'headers' => ['Phần mềm', 'Phiên bản', 'Nhà phát hành', 'Số máy', 'Bản quyền'], 'rows' => $out];
    }

    /** @return Report */
    private function health(): array
    {
        $assets = Asset::pluck('asset_tag', 'id');
        $hw = DB::table('inv_hardware')->where('is_current', true)->get()->keyBy('asset_id');
        $cpu = DB::table('inv_processors')->where('is_current', true)->get()->groupBy('asset_id');
        $disk = DB::table('inv_storages')->where('is_current', true)->select('asset_id', DB::raw('SUM(size_mb) as mb'))->groupBy('asset_id')->pluck('mb', 'asset_id');
        $free = DB::table('inv_drives')->where('is_current', true)->where('total_mb', '>', 0)
            ->select('asset_id', DB::raw('MIN(free_mb * 100.0 / total_mb) as pct'))->groupBy('asset_id')->pluck('pct', 'asset_id');
        $battery = DB::table('inv_batteries')->where('is_current', true)->select('asset_id', DB::raw('MIN(health_percent) as h'))->groupBy('asset_id')->pluck('h', 'asset_id');

        $lowDisk = (int) config('inventory.low_disk_percent');
        $worn = (int) config('inventory.battery_worn_percent');
        $old = (array) config('inventory.old_os_patterns', ['Windows 7', 'Windows 8', 'Windows 10', 'Vista', 'XP']);

        $rows = [];
        foreach ($hw as $assetId => $h) {
            $flags = [];
            if (isset($free[$assetId]) && $free[$assetId] < $lowDisk) {
                $flags[] = 'Sắp hết đĩa';
            }
            if (isset($battery[$assetId]) && $battery[$assetId] < $worn) {
                $flags[] = 'Pin chai';
            }
            foreach ($old as $p) {
                if (stripos((string) $h->os_name, $p) !== false) {
                    $flags[] = 'HĐH cũ';
                    break;
                }
            }

            $rows[] = [
                $assets[$assetId] ?? $assetId, $h->hostname, ($cpu[$assetId] ?? collect())->pluck('name')->implode(', '),
                $h->memory_total_mb, isset($disk[$assetId]) ? (int) $disk[$assetId] : null,
                isset($free[$assetId]) ? round($free[$assetId]).'%' : '', $battery[$assetId] ?? '',
                trim($h->os_name.' '.$h->os_version), implode('; ', $flags),
            ];
        }

        return ['title' => trans('admin/inventory/reports.health'), 'headers' => ['Tài sản', 'Tên máy', 'CPU', 'RAM (MB)', 'Ổ cứng (MB)', 'Đĩa trống tối thiểu', 'Pin (%)', 'HĐH', 'Cảnh báo'], 'rows' => $rows];
    }

    private function short(?string $v): string
    {
        return mb_strimwidth((string) $v, 0, 120, '…');
    }
}
