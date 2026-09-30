<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the agent with HTTP Basic.
 *
 * This is the ONLY scheme GLPI Agent supports: it cannot send arbitrary
 * headers, only the 'user' / 'password' pair from agent.cfg. And per
 * HTTP/Client.pm:271-300 it only resends with credentials AFTER a 401 that
 * carries a WWW-Authenticate header - without that header it gives up silently.
 */
class VerifyAgentRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('inventory.agent_user');
        $secret = (string) config('inventory.agent_secret');

        if ($secret === '') {
            return response('INVENTORY_AGENT_SECRET is not configured on the server', 503);
        }

        $sentUser = (string) $request->getUser();
        $sentPass = (string) $request->getPassword();

        if (! hash_equals($user, $sentUser) || ! hash_equals($secret, $sentPass)) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="QLTS Agent"',
            ]);
        }

        if (! Str::isUuid((string) $request->header('GLPI-Agent-ID'))) {
            return response('Missing or malformed GLPI-Agent-ID header', 400);
        }

        return $next($request);
    }
}
