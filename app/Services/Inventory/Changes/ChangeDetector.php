<?php

namespace App\Services\Inventory\Changes;

use App\Models\Inventory\InvChange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * So báo cáo mới với cây hiện tại. Phần 🟢 trả về để ghi ngay; phần 🔴 được
 * giữ nguyên như cũ trong cây và ghi thành inv_changes state=pending.
 */
class ChangeDetector
{
    private const META = ['id', 'asset_id', 'inv_snapshot_id', 'first_seen_at', 'last_seen_at', 'is_current'];

    /**
     * @param  list<array<string, mixed>>  $new
     * @return list<array<string, mixed>> các hàng thực sự được phép ghi vào cây
     */
    public function filter(int $assetId, int $snapshotId, string $section, string $table, array $new): array
    {
        $rule = ChangePolicy::rule($section);

        // Lần kiểm kê đầu tiên của máy là mốc chuẩn, không phải "thay đổi".
        if ($rule === null || ! DB::table($table)->where('asset_id', $assetId)->exists()) {
            return $new;
        }

        $old = [];
        foreach (DB::table($table)->where('asset_id', $assetId)->where('is_current', true)->get() as $r) {
            $old[$r->part_key] = array_diff_key((array) $r, array_flip(self::META));
        }

        $out = [];
        $detected = [];

        if ($rule['mode'] === 'fields') {
            foreach ($new as $row) {
                $was = $old[$row['part_key']] ?? null;
                foreach ($rule['fields'] as $field => $severity) {
                    if ($was !== null && $this->filled($was[$field] ?? null) && $this->norm($was[$field]) !== $this->norm($row[$field] ?? null)) {
                        $detected[] = $this->change($assetId, $snapshotId, $section, $row['part_key'], 'changed', $field, $was[$field], $row[$field] ?? null, $severity);
                        $row[$field] = $was[$field];
                    }
                }
                $out[] = $row;
            }
        } else {
            $newKeys = [];
            foreach ($new as $row) {
                $key = $row['part_key'];
                $newKeys[$key] = true;
                if (! isset($old[$key])) {
                    $detected[] = $this->change($assetId, $snapshotId, $section, $key, 'added', null, null, json_encode($row), 'warning');

                    continue;
                }
                if ($rule['mode'] === 'all' && $this->differs($old[$key], $row)) {
                    $detected[] = $this->change($assetId, $snapshotId, $section, $key, 'changed', '*', json_encode($old[$key]), json_encode($row), 'warning');
                    $out[] = $old[$key];

                    continue;
                }
                $out[] = $row;
            }
            foreach ($old as $key => $oldRow) {
                if (! isset($newKeys[$key])) {
                    $detected[] = $this->change($assetId, $snapshotId, $section, $key, 'removed', null, json_encode($oldRow), null, 'warning');
                    $out[] = $oldRow;
                }
            }
        }

        $this->record($assetId, $section, $detected);

        return $out;
    }

    /** @param list<array<string, mixed>> $detected */
    private function record(int $assetId, string $section, array $detected): void
    {
        $pending = InvChange::where('asset_id', $assetId)->where('section', $section)->where('state', 'pending')->get();
        $keep = [];

        foreach ($detected as $d) {
            $existing = $pending->first(fn ($p) => $p->part_key === $d['part_key']
                && $p->change_type === $d['change_type'] && $p->field === $d['field']
                && $p->new_value === $d['new_value'] && $p->old_value === $d['old_value']);

            if ($existing) {
                $keep[$existing->id] = true;

                continue;
            }
            $keep[InvChange::create($d)->id] = true;

            if ($d['severity'] === 'critical') {
                $this->mailCritical($d);
            }
        }

        // Bản kiểm kê mới không còn khác biệt đó nữa -> huỷ yêu cầu cũ.
        foreach ($pending as $p) {
            if (! isset($keep[$p->id])) {
                $p->update(['state' => 'rejected', 'note' => 'Tự huỷ: bản kiểm kê mới không còn khác biệt.']);
            }
        }
    }

    /** Thay đổi critical luôn gửi email ngay (spec §7); lỗi gửi mail không được làm hỏng việc nhận kiểm kê. */
    private function mailCritical(array $d): void
    {
        $to = config('inventory.alert_email');
        if (! $to) {
            return;
        }

        try {
            Mail::raw(
                "Tài sản #{$d['asset_id']}: {$d['section']}.{$d['field']} đổi từ '{$d['old_value']}' sang '{$d['new_value']}'. Cần duyệt tại Hàng chờ duyệt kiểm kê.",
                fn ($m) => $m->to($to)->subject('[Kiểm kê] Thay đổi quan trọng tài sản #'.$d['asset_id'])
            );
        } catch (\Throwable $e) {
            Log::warning('[qlts-agent] critical mail failed: '.$e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function change(int $assetId, int $snapshotId, string $section, string $key, string $type, ?string $field, mixed $old, mixed $new, string $severity): array
    {
        return [
            'asset_id' => $assetId, 'inv_snapshot_id' => $snapshotId, 'section' => $section, 'part_key' => $key,
            'change_type' => $type, 'field' => $field, 'old_value' => $old === null ? null : (string) $old,
            'new_value' => $new === null ? null : (string) $new, 'severity' => $severity, 'state' => 'pending',
        ];
    }

    /**
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     */
    private function differs(array $old, array $new): bool
    {
        foreach ($new as $k => $v) {
            if (array_key_exists($k, $old) && $this->norm($old[$k]) !== $this->norm($v)) {
                return true;
            }
        }

        return false;
    }

    private function norm(mixed $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }

        return is_bool($v) ? (string) (int) $v : (string) $v;
    }

    private function filled(mixed $v): bool
    {
        return $v !== null && trim((string) $v) !== '';
    }
}
