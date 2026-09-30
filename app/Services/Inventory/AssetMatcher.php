<?php

namespace App\Services\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvUnmatched;

/**
 * Matches a workstation to an asset in the register.
 *
 * Order: machine serial (bios.ssn) -> asset tag -> asset name. Stops at the
 * first hit.
 *
 * RB-8: NEVER match by RustDesk ID - it is duplicated or changes when a
 * machine is ghosted/cloned, so matching on it would attach the report to the
 * wrong asset record.
 *
 * RB-9: never creates an asset. An unknown machine is only recorded in
 * inv_unmatched for an administrator to assign by hand.
 */
class AssetMatcher
{
    /**
     * @param  array<string, mixed>  $content
     */
    public function match(array $content, InvAgent $agent): ?int
    {
        $serial = $this->clean(SectionReader::value(
            SectionReader::rows($content, 'bios')[0] ?? [], 'ssn'
        ));

        $hardware = SectionReader::rows($content, 'hardware')[0] ?? [];
        $hostname = $this->clean(SectionReader::value($hardware, 'name'));
        $machineUuid = $this->clean(SectionReader::value($hardware, 'uuid'));

        $id = $this->by('serial', $serial)
            ?? $this->by('asset_tag', $hostname)
            ?? $this->by('name', $hostname);

        if ($id !== null) {
            return $id;
        }

        $this->recordUnmatched($agent, $hostname, $serial, $machineUuid);

        return null;
    }

    /**
     * Case- and whitespace-insensitive lookup on one asset column.
     */
    private function by(string $column, ?string $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return Asset::whereRaw('LOWER(TRIM('.$column.')) = ?', [strtolower($value)])->value('id');
    }

    private function recordUnmatched(
        InvAgent $agent,
        ?string $hostname,
        ?string $serial,
        ?string $machineUuid
    ): void {
        InvUnmatched::updateOrCreate(
            ['inv_agent_id' => $agent->id],
            [
                'hostname' => $hostname,
                'serial' => $serial,
                'machine_uuid' => $machineUuid,
                'reason' => 'no_asset_match',
            ]
        );
    }

    /**
     * Trimmed original value (case kept for display), or null when blank.
     */
    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = trim($value);

        return $clean === '' ? null : $clean;
    }
}
