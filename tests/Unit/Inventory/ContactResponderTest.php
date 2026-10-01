<?php

namespace Tests\Unit\Inventory;

use App\Services\Inventory\ContactResponder;
use Tests\TestCase;

class ContactResponderTest extends TestCase
{
    // Protocol/Contact.pm:31-40 - is_valid_message() requires BOTH 'status' AND
    // expiration > 0. Missing either, the agent decides this is not a GLPI
    // server and falls back to the legacy XML protocol.
    public function test_answer_has_status_and_positive_expiration(): void
    {
        $answer = (new ContactResponder)->answer();

        $this->assertSame('ok', $answer['status']);
        $this->assertGreaterThan(0, $answer['expiration']);
    }

    public function test_answer_enables_inventory_and_deploy(): void
    {
        $answer = (new ContactResponder)->answer();

        $this->assertArrayHasKey('inventory', $answer['tasks']);
        $this->assertArrayHasKey('deploy', $answer['tasks']);
    }

    // Target/Server.pm:126-144 - setServerTaskSupport() ignores a task without BOTH
    // 'server' and 'version', and doProlog() then returns true. The agent would
    // send a legacy XML PROLOG on every run, with no GLPI-Agent-ID header.
    public function test_inventory_task_declares_server_and_version_so_agent_skips_prolog(): void
    {
        $inventory = (new ContactResponder)->answer()['tasks']['inventory'];

        $this->assertSame('glpi', $inventory['server']);
        $this->assertNotEmpty($inventory['version']);
    }

    // RB-6 / spec 12.1: enabling collect opens arbitrary command execution under
    // SYSTEM on all 300 machines. Never.
    public function test_answer_never_enables_collect(): void
    {
        $answer = (new ContactResponder)->answer();

        $this->assertArrayNotHasKey('collect', $answer['tasks']);
    }

    public function test_frequencies_come_from_config(): void
    {
        config([
            'inventory.inventory_interval_hours' => 12,
            'inventory.deploy_poll_hours' => 2,
        ]);

        $answer = (new ContactResponder)->answer();

        $this->assertSame(12, $answer['expiration']);
        $this->assertSame(12, $answer['tasks']['inventory']['params'][0]['frequency']);
        $this->assertSame(2, $answer['tasks']['deploy']['params'][0]['frequency']);
    }
}
