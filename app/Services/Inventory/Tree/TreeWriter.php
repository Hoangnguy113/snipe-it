<?php

namespace App\Services\Inventory\Tree;

use App\Services\Inventory\Changes\ChangeDetector;
use Illuminate\Support\Facades\DB;

/**
 * Ghi 1 section vào cây: khớp theo part_key, hàng vắng mặt chuyển is_current=false
 * (giữ lịch sử để GĐ3 so sánh/duyệt).
 */
class TreeWriter
{
    /**
     * @param  list<array<string, mixed>>  $rows  mỗi hàng có 'part_key'
     */
    public function sync(string $table, int $assetId, int $snapshotId, array $rows): void
    {
        $now = now();
        $current = DB::table($table)->where('asset_id', $assetId)->where('is_current', true)
            ->pluck('id', 'part_key')->all();

        $inserts = [];
        $seen = [];
        foreach ($rows as $row) {
            $key = $row['part_key'];
            $seen[$key] = true;
            $values = $row + ['asset_id' => $assetId];
            $values['inv_snapshot_id'] = $snapshotId;
            $values['last_seen_at'] = $now;

            if (isset($current[$key])) {
                DB::table($table)->where('id', $current[$key])->update($values);
            } else {
                $inserts[] = $values + ['first_seen_at' => $now, 'is_current' => true];
            }
        }

        foreach (array_chunk($inserts, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $gone = array_values(array_diff_key($current, $seen));
        if ($gone !== []) {
            DB::table($table)->whereIn('id', $gone)->update(['is_current' => false]);
        }
    }

    /** Ghi/đè đúng 1 hàng (dùng khi duyệt thay đổi). @param array<string, mixed> $row */
    public function put(string $table, int $assetId, int $snapshotId, array $row): void
    {
        $this->sync1($table, $assetId, $snapshotId, $row);
    }

    public function retire(string $table, int $assetId, string $partKey): void
    {
        DB::table($table)->where('asset_id', $assetId)->where('part_key', $partKey)->where('is_current', true)
            ->update(['is_current' => false]);
    }

    /** @param array<string, mixed> $values */
    public function patch(string $table, int $assetId, string $partKey, array $values, int $snapshotId): void
    {
        DB::table($table)->where('asset_id', $assetId)->where('part_key', $partKey)->where('is_current', true)
            ->update($values + ['inv_snapshot_id' => $snapshotId, 'last_seen_at' => now()]);
    }

    /** @param array<string, mixed> $row */
    private function sync1(string $table, int $assetId, int $snapshotId, array $row): void
    {
        $id = DB::table($table)->where('asset_id', $assetId)->where('part_key', $row['part_key'])
            ->where('is_current', true)->value('id');
        $values = $row + ['asset_id' => $assetId];
        $values['inv_snapshot_id'] = $snapshotId;
        $values['last_seen_at'] = now();

        if ($id) {
            DB::table($table)->where('id', $id)->update($values);
        } else {
            DB::table($table)->insert($values + ['first_seen_at' => now(), 'is_current' => true]);
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function syncAll(int $assetId, int $snapshotId, array $content): void
    {
        $mapped = TreeSections::map($content);

        DB::transaction(function () use ($assetId, $snapshotId, $mapped) {
            $detector = app(ChangeDetector::class);

            foreach (TreeSections::tables() as $section => $table) {
                $rows = $detector->filter($assetId, $snapshotId, $section, $table, $mapped[$section] ?? []);
                $this->sync($table, $assetId, $snapshotId, $rows);
            }
        });
    }
}
