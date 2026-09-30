<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InvAgent;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ContactHandshakeTest extends TestCase
{
    private string $agentUuid;

    protected function setUp(): void
    {
        parent::setUp();

        // CheckForSetup redirects to /setup until a user exists.
        User::factory()->create();

        config([
            'inventory.agent_user' => 'qlts-agent',
            'inventory.agent_secret' => 'test-secret',
            'inventory.inventory_interval_hours' => 24,
            'inventory.deploy_poll_hours' => 4,
        ]);
        $this->agentUuid = Str::uuid()->toString();
    }

    private function sendRaw(string $body): TestResponse
    {
        return $this->call('POST', '/agent/inventory', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('qlts-agent:test-secret'),
            'HTTP_GLPI_AGENT_ID' => $this->agentUuid,
            'CONTENT_TYPE' => 'application/x-compress-zlib',
        ], $body);
    }

    private function sendContact(array $message): TestResponse
    {
        return $this->sendRaw(gzcompress(json_encode($message)));
    }

    public function test_contact_returns_valid_contact_answer(): void
    {
        $response = $this->sendContact([
            'action' => 'contact',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'name' => 'GLPI-Agent',
            'version' => '1.20',
            'tag' => 'PHONG-KE-TOAN',
            'enabled-tasks' => ['inventory', 'deploy'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('expiration', 24)
            ->assertJsonPath('tasks.inventory.params.0.frequency', 24)
            ->assertJsonPath('tasks.deploy.params.0.frequency', 4);

        $this->assertArrayNotHasKey('collect', $response->json('tasks'));
    }

    public function test_contact_registers_the_agent(): void
    {
        $this->sendContact([
            'action' => 'contact',
            'deviceid' => 'PC-045-2026-09-30-10-15-00',
            'version' => '1.20',
            'tag' => 'PHONG-KE-TOAN',
        ])->assertOk();

        $agent = InvAgent::where('deviceid', 'PC-045-2026-09-30-10-15-00')->first();

        $this->assertNotNull($agent);
        $this->assertSame($this->agentUuid, $agent->agent_uuid);
        $this->assertSame('PHONG-KE-TOAN', $agent->tag);
        $this->assertSame('1.20', $agent->agent_version);
        $this->assertNotNull($agent->last_contact_at);
    }

    public function test_contact_twice_does_not_create_a_second_agent(): void
    {
        $message = ['action' => 'contact', 'deviceid' => 'PC-045', 'version' => '1.20'];

        $this->sendContact($message)->assertOk();
        $this->sendContact($message)->assertOk();

        $this->assertSame(1, InvAgent::where('deviceid', 'PC-045')->count());
    }

    // RB-4: UPPERCASE keys (legacy XML protocol converted to JSON) must be readable.
    public function test_contact_accepts_uppercase_keys(): void
    {
        $this->sendContact([
            'ACTION' => 'contact',
            'DEVICEID' => 'PC-UPPER',
            'VERSION' => '1.20',
        ])->assertOk()->assertJsonPath('status', 'ok');

        $this->assertNotNull(InvAgent::where('deviceid', 'PC-UPPER')->first());
    }

    public function test_contact_without_deviceid_returns_400(): void
    {
        $this->sendContact(['action' => 'contact', 'version' => '1.20'])
            ->assertStatus(400);
    }

    public function test_unreadable_body_returns_400(): void
    {
        $this->sendRaw("\x01\x02garbage")->assertStatus(400);
    }
}
