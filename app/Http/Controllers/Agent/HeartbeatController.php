<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvHeartbeat;
use App\Services\Inventory\InvalidPayloadException;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Nhịp tim ~1KB / 5 phút: cho biết máy đang bật, ai đăng nhập, tải CPU/RAM/đĩa.
 */
class HeartbeatController extends Controller
{
    public function store(Request $request, PayloadDecoder $decoder): JsonResponse|Response
    {
        try {
            $payload = $decoder->decode($request->getContent(), $request->header('Content-Type'));
        } catch (InvalidPayloadException $e) {
            return response($e->getMessage(), 400);
        }

        if (! $payload->isJson()) {
            return response('Heartbeat must be JSON', 400);
        }

        $m = $payload->toArray();
        $deviceid = SectionReader::value($m, 'deviceid');
        if (! is_string($deviceid) || $deviceid === '') {
            return response('Missing deviceid', 400);
        }

        $agent = InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            ['agent_uuid' => $request->header('GLPI-Agent-ID'), 'ip' => $request->ip(), 'last_heartbeat_at' => now(), 'state' => 'active']
        );

        $rustdesk = SectionReader::value($m, 'rustdesk_running');
        InvHeartbeat::create([
            'inv_agent_id' => $agent->id,
            'received_at' => now(),
            'cpu_percent' => $this->num(SectionReader::value($m, 'cpu_percent')),
            'ram_percent' => $this->num(SectionReader::value($m, 'ram_percent')),
            'disks' => is_array(SectionReader::value($m, 'disks')) ? SectionReader::value($m, 'disks') : null,
            'logged_user' => SectionReader::value($m, 'logged_user'),
            'ip' => SectionReader::value($m, 'ip') ?? $request->ip(),
            'rustdesk_running' => $rustdesk === null ? null : filter_var($rustdesk, FILTER_VALIDATE_BOOLEAN),
            'uptime_sec' => is_numeric($u = SectionReader::value($m, 'uptime_sec')) ? (int) $u : null,
        ]);

        return response()->json(['status' => 'ok']);
    }

    private function num(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }
}
