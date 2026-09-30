<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The global CheckForSetup middleware redirects every request to /setup
        // until at least one user exists. A running install always has one.
        User::factory()->create();

        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'test-secret',
        ]);
    }

    private function basic(): string
    {
        return 'Basic '.base64_encode('qlts-agent:test-secret');
    }

    // HTTP/Client.pm:271-300 - the agent ONLY resends with credentials after a
    // 401 that carries a WWW-Authenticate header. Without it the agent silently
    // gives up.
    public function test_missing_credentials_returns_401_with_basic_challenge(): void
    {
        $response = $this->postJson('/agent/inventory', []);

        $response->assertStatus(401);
        $this->assertStringContainsString('Basic realm=', $response->headers->get('WWW-Authenticate'));
    }

    public function test_wrong_password_returns_401(): void
    {
        $this->withHeaders([
            'Authorization' => 'Basic '.base64_encode('qlts-agent:wrong-password'),
            'GLPI-Agent-ID' => Str::uuid()->toString(),
        ])->post('/agent/inventory', [])->assertStatus(401);
    }

    public function test_missing_agent_id_header_returns_400(): void
    {
        $this->withHeaders(['Authorization' => $this->basic()])
            ->post('/agent/inventory', [])
            ->assertStatus(400);
    }

    public function test_malformed_agent_id_returns_400(): void
    {
        $this->withHeaders([
            'Authorization' => $this->basic(),
            'GLPI-Agent-ID' => 'not-a-uuid',
        ])->post('/agent/inventory', [])->assertStatus(400);
    }

    // The agent route is registered OUTSIDE the 'web' middleware group, so
    // VerifyCsrfToken (app/Http/Kernel.php:76) does not run. This pins it: no
    // CSRF token and still no 419.
    public function test_valid_request_is_not_blocked_by_csrf(): void
    {
        // withHeaders() is not applied by call(), so the headers go in as server vars.
        $response = $this->call('POST', '/agent/inventory', [], [], [], [
            'HTTP_AUTHORIZATION' => $this->basic(),
            'HTTP_GLPI_AGENT_ID' => Str::uuid()->toString(),
            'CONTENT_TYPE' => 'application/x-compress-zlib',
        ], gzcompress('{"action":"contact","deviceid":"PC-045"}'));

        $this->assertNotSame(419, $response->getStatusCode());
        $this->assertNotSame(401, $response->getStatusCode());
        $this->assertNotSame(400, $response->getStatusCode());
    }
}
