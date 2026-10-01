<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvPackage;
use App\Services\Inventory\Deploy\DeployService;
use App\Services\Inventory\InvalidPayloadException;
use App\Services\Inventory\PayloadDecoder;
use App\Services\Inventory\SectionReader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeployAgentController extends Controller
{
    public function __construct(private readonly DeployService $deploy) {}

    public function handle(Request $request, PayloadDecoder $decoder): JsonResponse|Response
    {
        if (! $this->deploy->isOpen($request)) {
            return response()->json((object) []);
        }

        try {
            $m = $decoder->decode($request->getContent(), $request->header('Content-Type'))->toArray();
        } catch (InvalidPayloadException $e) {
            return response($e->getMessage(), 400);
        }

        $agent = InvAgent::where('deviceid', (string) SectionReader::value($m, 'machineid'))->first();
        if ($agent === null) {
            return response()->json((object) []);
        }

        return match (strtolower((string) SectionReader::value($m, 'action'))) {
            'getjobs' => response()->json($this->deploy->jobsFor($agent) ?: (object) []),
            'setstatus' => tap(response()->json(['status' => 'ok']), fn () => $this->deploy->setStatus($agent, $m)),
            default => response('Unsupported action', 400),
        };
    }

    /** Mirror của agent: URL = mirror + "a/ab/<sha512>" (Deploy/File.pm:259-265). */
    public function file(Request $request, string $a, string $ab, string $sha512): BinaryFileResponse|Response
    {
        if (! $this->deploy->isOpen($request) || ! preg_match('/^[0-9a-f]{128}$/', $sha512)
            || $a !== $sha512[0] || $ab !== substr($sha512, 0, 2)) {
            return response('Not found', 404);
        }

        $package = InvPackage::where('sha512', $sha512)->first();
        if ($package === null || ! is_file($package->absolutePath())) {
            return response('Not found', 404);
        }

        return response()->download($package->absolutePath(), $package->file_name);
    }
}
