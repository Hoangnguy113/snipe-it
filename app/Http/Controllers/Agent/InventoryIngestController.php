<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Jobs\Inventory\ProcessSnapshot;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\ContactResponder;
use App\Services\Inventory\DecodedPayload;
use App\Services\Inventory\InvalidPayloadException;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InventoryIngestController extends Controller
{
    public function __construct(
        private readonly PayloadDecoder $decoder,
        private readonly ContactResponder $contactResponder,
    ) {}

    public function store(Request $request): JsonResponse|Response
    {
        try {
            $payload = $this->decoder->decode(
                $request->getContent(),
                $request->header('Content-Type')
            );
        } catch (InvalidPayloadException $e) {
            Log::warning('[qlts-agent] unreadable body: '.$e->getMessage());

            return response($e->getMessage(), 400);
        }

        // The legacy XML protocol is only tolerated for the handshake: answer with
        // a JSON CONTACT so the agent switches to JSON from then on
        // (HTTP/Client/OCS.pm:90-110).
        if (! $payload->isJson()) {
            return response()->json($this->contactResponder->answer());
        }

        $message = $payload->toArray();
        $action = strtolower((string) SectionReader::value($message, 'action'));

        return match ($action) {
            'contact' => $this->handleContact($request, $message),
            'inventory' => $this->handleInventory($request, $message, $payload),
            default => response('Unsupported action: '.$action, 400),
        };
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleContact(Request $request, array $message): JsonResponse|Response
    {
        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            return response('Missing deviceid', 400);
        }

        InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            [
                'agent_uuid' => $request->header('GLPI-Agent-ID'),
                'tag' => SectionReader::value($message, 'tag'),
                'agent_version' => SectionReader::value($message, 'version'),
                'ip' => $request->ip(),
                'last_contact_at' => now(),
                'state' => 'active',
            ]
        );

        return response()->json($this->contactResponder->answer());
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function handleInventory(
        Request $request,
        array $message,
        DecodedPayload $payload
    ): JsonResponse|Response {
        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            return response('Missing deviceid', 400);
        }

        $agent = InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            [
                'agent_uuid' => $request->header('GLPI-Agent-ID'),
                'ip' => $request->ip(),
                'last_contact_at' => now(),
                'state' => 'active',
            ]
        );

        $hash = hash('sha256', $payload->content);

        // The agent resends an identical inventory when nothing changed; skip it
        // so the table does not balloon (300 machines x 2MB x daily). Compare
        // against the LATEST snapshot only, so an A -> B -> A change is kept.
        $latestHash = InvSnapshot::where('inv_agent_id', $agent->id)
            ->latest('id')
            ->value('content_hash');

        $exists = $latestHash === $hash;

        if ($exists) {
            $agent->update(['last_inventory_at' => now()]);

            return response()->json(['status' => 'ok']);
        }

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress($payload->content),
            'content_hash' => $hash,
            'protocol' => $payload->protocol,
            'received_at' => now(),
        ]);

        ProcessSnapshot::dispatch($snapshot->id);

        return response()->json(['status' => 'ok']);
    }
}
