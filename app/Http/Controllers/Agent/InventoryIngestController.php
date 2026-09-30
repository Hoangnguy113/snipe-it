<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvAgent;
use App\Services\Inventory\ContactResponder;
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
}
