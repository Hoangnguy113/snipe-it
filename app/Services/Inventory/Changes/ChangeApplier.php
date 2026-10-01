<?php

namespace App\Services\Inventory\Changes;

use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvRemovedPart;
use App\Models\Maintenance;
use App\Models\MaintenanceType;
use App\Models\User;
use App\Services\Inventory\Tree\TreeSections;
use App\Services\Inventory\Tree\TreeWriter;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Duyệt / từ chối một thay đổi chờ duyệt (spec §8.1).
 * QĐ-5: linh kiện tháo ra KHÔNG cộng vào kho `components`.
 */
class ChangeApplier
{
    public const MAINTENANCE_TYPE = 'Thay thế linh kiện';

    public function __construct(private readonly TreeWriter $tree) {}

    public function approve(InvChange $change, ?User $by = null): void
    {
        if ($change->state !== 'pending') {
            throw new RuntimeException('Thay đổi này đã được xử lý.');
        }

        DB::transaction(function () use ($change, $by) {
            $asset = Asset::findOrFail($change->asset_id);

            if ($change->section === 'khoa_phong') {
                $this->moveToLocation($asset, (int) $change->new_value);
            } else {
                $this->applyToTree($change, $asset);
            }

            $change->update(['state' => 'approved', 'approved_by' => $by?->id, 'approved_at' => now()]);
            $this->log($asset, $by, 'Kiểm kê tự động - đã duyệt: '.$this->describe($change));
        });
    }

    public function reject(InvChange $change, ?User $by = null, ?string $note = null): void
    {
        if ($change->state !== 'pending') {
            throw new RuntimeException('Thay đổi này đã được xử lý.');
        }

        $change->update(['state' => 'rejected', 'approved_by' => $by?->id, 'approved_at' => now(), 'note' => $note]);
    }

    private function applyToTree(InvChange $change, Asset $asset): void
    {
        $table = TreeSections::tables()[$change->section];
        $snapshot = (int) $change->inv_snapshot_id;

        if ($change->change_type === 'removed') {
            $old = $change->oldRow() ?? [];
            $this->tree->retire($table, $asset->id, $change->part_key);
            $this->recordRemovedPart($change, $asset, $old);

            return;
        }

        if ($change->field !== null && $change->field !== '*') {
            $this->tree->patch($table, $asset->id, $change->part_key, [$change->field => $change->new_value], $snapshot);

            return;
        }

        $this->tree->put($table, $asset->id, $snapshot, $change->newRow() ?? []);
    }

    /** @param array<string, mixed> $old */
    private function recordRemovedPart(InvChange $change, Asset $asset, array $old): void
    {
        $name = $old['name'] ?? $old['model'] ?? trim(($old['manufacturer'] ?? '').' '.($old['capacity_mb'] ?? '').' '.($old['mem_type'] ?? ''));
        $name = $name !== '' ? $name : $change->part_key;

        $part = InvRemovedPart::create([
            'asset_id' => $asset->id, 'part_type' => $change->section, 'name' => $name,
            'serial' => $old['serial'] ?? $old['msn'] ?? null, 'specs' => $old,
            'detected_at' => $change->created_at, 'condition' => 'hong', 'disposal_state' => 'luu_kho',
            'created_by' => auth()->id(),
        ]);

        $type = MaintenanceType::firstOrCreate(['name' => self::MAINTENANCE_TYPE]);
        $maintenance = new Maintenance;
        $maintenance->fill([
            'item_id' => $asset->id, 'item_type' => Asset::class,
            'maintenance_type_id' => $type->id,
            'name' => mb_substr('Thay '.$change->section.': '.$name, 0, 100),
            'start_date' => now(), 'notes' => 'Tạo tự động từ kiểm kê. Linh kiện cũ: '.$name.' ('.($part->serial ?? 'không serial').')',
        ]);
        if (! $maintenance->save()) {
            throw new RuntimeException('Không tạo được phiếu bảo trì: '.json_encode($maintenance->getErrors()));
        }

        $part->update(['maintenance_id' => $maintenance->id]);
    }

    /** QĐ-13: location_id chỉ đổi khi có người duyệt. */
    public function moveToLocation(Asset $asset, int $locationId): void
    {
        $asset->location_id = $locationId;
        if (! $asset->save()) {
            throw new RuntimeException('Không cập nhật được vị trí: '.json_encode($asset->getErrors()));
        }
    }

    public function log(Asset $asset, ?User $by, string $note): void
    {
        $log = new Actionlog;
        $log->item_type = Asset::class;
        $log->item_id = $asset->id;
        $log->location_id = null;
        $log->action_date = date('Y-m-d H:i:s');
        $log->note = $note;
        $log->created_by = $by?->id ?? -1;
        $log->company_id = $asset->company_id;
        $log->logaction('update');
        $log->save();
    }

    private function describe(InvChange $c): string
    {
        return "{$c->section} {$c->change_type} [{$c->part_key}]";
    }
}
