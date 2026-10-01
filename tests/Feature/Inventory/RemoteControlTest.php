<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvRemoteSession;
use App\Models\User;
use Tests\TestCase;

class RemoteControlTest extends TestCase
{
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();
        $this->asset = Asset::factory()->create();
        InvAgent::create(['deviceid' => 'd', 'state' => 'active', 'asset_id' => $this->asset->id, 'rustdesk_id' => '16659046', 'last_inventory_at' => now()]);
        config(['inventory.remote_require_2fa' => false]);
    }

    public function test_user_without_permission_gets_403_and_no_log(): void
    {
        $this->actingAs(User::factory()->viewAssets()->create())
            ->post(route('inventory.remote.start', $this->asset))
            ->assertForbidden();

        $this->assertSame(0, InvRemoteSession::count());
    }

    public function test_permitted_user_is_redirected_to_rustdesk_and_logged(): void
    {
        $user = User::factory()->create(['permissions' => json_encode(['remote.control' => '1'])]);

        $this->actingAs($user)
            ->post(route('inventory.remote.start', $this->asset))
            ->assertRedirect('rustdesk://16659046');

        $s = InvRemoteSession::first();
        $this->assertSame($user->id, $s->user_id);
        $this->assertSame($this->asset->id, $s->asset_id);
        $this->assertSame('16659046', $s->rustdesk_id);
    }

    public function test_two_factor_switch_blocks_when_on_and_not_when_off(): void
    {
        $user = User::factory()->superuser()->create();

        config(['inventory.remote_require_2fa' => true]);
        $this->actingAs($user)->post(route('inventory.remote.start', $this->asset))->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, InvRemoteSession::count());

        config(['inventory.remote_require_2fa' => false]);
        $this->actingAs($user)->post(route('inventory.remote.start', $this->asset))->assertRedirect('rustdesk://16659046');
        $this->assertSame(1, InvRemoteSession::count());
    }

    public function test_machine_without_rustdesk_id_is_not_logged(): void
    {
        $other = Asset::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('inventory.remote.start', $other))->assertSessionHas('error');
        $this->assertSame(0, InvRemoteSession::count());
    }

    public function test_log_page_lists_sessions(): void
    {
        $admin = User::factory()->superuser()->create();
        InvRemoteSession::create(['user_id' => $admin->id, 'asset_id' => $this->asset->id, 'rustdesk_id' => '999', 'started_at' => now(), 'operator_ip' => '10.1.1.1']);

        $this->actingAs($admin)->get(route('inventory.remote.log'))->assertOk()->assertSee('10.1.1.1');
    }
}
