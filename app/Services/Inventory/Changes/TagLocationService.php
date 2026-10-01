<?php

namespace App\Services\Inventory\Changes;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvTagLocation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Nhãn khoa phòng do người cài khai (spec §8.2) — là lời khai, chỉ thành vị trí
 * chính thức sau khi có người duyệt (QĐ-13).
 */
class TagLocationService
{
    public const UNASSIGNED = 'CHUA-PHAN-NHOM';

    public function __construct(private readonly ChangeApplier $applier) {}

    public function observe(InvAgent $agent): void
    {
        $tag = trim((string) $agent->tag);
        $asset = $agent->asset_id ? Asset::find($agent->asset_id) : null;

        // QĐ-14: chỉ ĐIỀN CHỖ TRỐNG bằng Kho, không bao giờ ghi đè vị trí đã có.
        $stock = config('inventory.stock_location_id');
        if ($asset && $asset->location_id === null && $stock) {
            $this->applier->moveToLocation($asset, (int) $stock);
        }

        if ($tag === '') {
            return;
        }

        $row = InvTagLocation::firstOrCreate(
            ['tag' => $tag],
            ['state' => $tag === self::UNASSIGNED ? 'ignored' : 'pending', 'first_seen_at' => now()]
        );
        $row->update(['agent_count' => InvAgent::where('tag', $tag)->count()]);

        if ($row->state !== 'approved' || ! $row->location_id || ! $asset) {
            return;
        }
        if ((int) $asset->location_id === (int) $row->location_id) {
            return;
        }

        InvChange::firstOrCreate(
            ['asset_id' => $asset->id, 'section' => 'khoa_phong', 'part_key' => $tag, 'state' => 'pending'],
            ['change_type' => 'changed', 'old_value' => $asset->location_id, 'new_value' => (string) $row->location_id, 'severity' => 'warning']
        );
    }

    /** Duyệt theo lô: mọi máy mang nhãn này được ghi location_id, mỗi máy 1 dòng ActionLog. */
    public function assign(InvTagLocation $row, int $locationId, ?User $by = null): int
    {
        return DB::transaction(function () use ($row, $locationId, $by) {
            $row->update(['state' => 'approved', 'location_id' => $locationId, 'approved_by' => $by?->id, 'approved_at' => now()]);

            $moved = 0;
            $assetIds = InvAgent::where('tag', $row->tag)->whereNotNull('asset_id')->pluck('asset_id')->unique();
            foreach (Asset::whereIn('id', $assetIds)->get() as $asset) {
                if ((int) $asset->location_id !== $locationId) {
                    $this->applier->moveToLocation($asset, $locationId);
                    $this->applier->log($asset, $by, "Kiểm kê tự động - duyệt nhãn khoa phòng '{$row->tag}'");
                    $moved++;
                }
            }

            InvChange::where('section', 'khoa_phong')->where('part_key', $row->tag)->where('state', 'pending')
                ->update(['state' => 'approved', 'approved_by' => $by?->id, 'approved_at' => now()]);

            return $moved;
        });
    }

    /** QĐ-11: chỉ admin tạo khoa phòng mới, và chỉ từ màn hình duyệt. */
    public function createLocationAndAssign(InvTagLocation $row, string $name, ?User $by = null): int
    {
        $location = new Location;
        $location->name = $name;
        $location->created_by = $by?->id;
        $location->save();

        return $this->assign($row, $location->id, $by);
    }

    public function ignore(InvTagLocation $row, ?User $by = null): void
    {
        $row->update(['state' => 'ignored', 'approved_by' => $by?->id, 'approved_at' => now()]);
    }
}
