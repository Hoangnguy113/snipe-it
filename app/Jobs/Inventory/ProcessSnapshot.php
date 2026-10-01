<?php

namespace App\Jobs\Inventory;

use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\AssetMatcher;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Parses an inventory report outside the request.
 *
 * With 300+ machines a 1-3MB report must not be parsed inside the request -
 * the agent only needs to know the server RECEIVED it.
 *
 * GD1 does the minimum: match the asset, store the RustDesk ID, mark the
 * snapshot processed. The full component tree is GD2's job.
 */
class ProcessSnapshot implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(public int $snapshotId) {}

    public function handle(PayloadDecoder $decoder, AssetMatcher $matcher): void
    {
        $snapshot = InvSnapshot::with('agent')->find($this->snapshotId);

        if ($snapshot === null) {
            return;
        }

        try {
            $content = SectionReader::section(
                $decoder->decode(gzuncompress($snapshot->payload) ? $snapshot->payload : '', 'application/x-compress-zlib')->toArray(),
                'content'
            );

            $agent = $snapshot->agent;

            $assetId = $matcher->match($content, $agent);

            $agent->update([
                'asset_id' => $assetId,
                'hostname' => SectionReader::value(
                    SectionReader::rows($content, 'hardware')[0] ?? [], 'name'
                ),
                'rustdesk_id' => $this->rustdeskId($content),
                'last_inventory_at' => now(),
            ]);

            $snapshot->update([
                'asset_id' => $assetId,
                'processed_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('[qlts-agent] failed to process snapshot '.$this->snapshotId.': '.$e->getMessage());

            $snapshot->update(['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function rustdeskId(array $content): ?string
    {
        foreach (SectionReader::rows($content, 'remote_mgmt') as $row) {
            $type = strtolower((string) SectionReader::value($row, 'type'));

            if ($type === 'rustdesk') {
                $id = SectionReader::value($row, 'id');

                return is_scalar($id) && (string) $id !== '' ? (string) $id : null;
            }
        }

        return null;
    }
}
