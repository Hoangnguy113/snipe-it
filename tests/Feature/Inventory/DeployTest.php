<?php

namespace Tests\Feature\Inventory;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvDeployTarget;
use App\Models\Inventory\InvPackage;
use App\Models\User;
use App\Services\Inventory\Deploy\DeployService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DeployTest extends TestCase
{
    private Asset $asset;

    private InvAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create();
        config([
            'inventory.agent_user' => 'qlts-agent', 'inventory.agent_secret' => 'test-secret',
            'inventory.deploy_enabled' => true, 'inventory.deploy_allow_http' => true,
        ]);
        $this->asset = Asset::factory()->create();
        $this->agent = InvAgent::create(['deviceid' => 'PC-DEP', 'state' => 'active', 'asset_id' => $this->asset->id]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/inventory-packages'));
        parent::tearDown();
    }

    private function agentPost(string $path, array $body): TestResponse
    {
        return $this->call('POST', $path, [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode('qlts-agent:test-secret'),
            'HTTP_GLPI_AGENT_ID' => Str::uuid()->toString(), 'CONTENT_TYPE' => 'application/json',
        ], json_encode($body));
    }

    private function package(): InvPackage
    {
        $file = UploadedFile::fake()->createWithContent('app.msi', 'FAKE-MSI-CONTENT');

        return app(DeployService::class)->storePackage($file, [
            'name' => 'App', 'install_cmd' => 'msiexec /i app.msi /qn',
            'checks' => [['type' => 'freespaceGreater', 'path' => 'C:\\', 'value' => '2000']],
        ], null);
    }

    public function test_package_sha512_is_computed(): void
    {
        $p = $this->package();

        $this->assertSame(hash('sha512', 'FAKE-MSI-CONTENT'), $p->sha512);
    }

    public function test_get_jobs_returns_valid_job_with_four_keys_and_file_map(): void
    {
        $p = $this->package();
        $job = app(DeployService::class)->createJob($p, 'asset', [$this->asset->id], null);

        $r = $this->agentPost('/agent/deploy', ['action' => 'getJobs', 'machineid' => 'PC-DEP'])->assertOk();

        $j = $r->json('jobs.0');
        $this->assertSame($job->uuid, $j['uuid']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]+$/i', $j['uuid']);
        foreach (['uuid', 'associatedFiles', 'actions', 'checks'] as $k) {
            $this->assertArrayHasKey($k, $j);
        }
        $this->assertSame('msiexec /i app.msi /qn', $j['actions'][0]['cmd']['exec']);
        $file = $r->json('associatedFiles')[$p->sha512];
        foreach (['mirrors', 'multiparts', 'name', 'p2p-retention-duration', 'p2p', 'uncompress'] as $k) {
            $this->assertArrayHasKey($k, $file);
        }
        $this->assertSame('running', InvDeployTarget::first()->state);
    }

    public function test_agent_can_download_file_and_progress_reaches_ok(): void
    {
        $p = $this->package();
        $job = app(DeployService::class)->createJob($p, 'asset', [$this->asset->id], null);
        $this->agentPost('/agent/deploy', ['action' => 'getJobs', 'machineid' => 'PC-DEP']);

        $url = '/agent/deploy/file/'.$p->sha512[0].'/'.substr($p->sha512, 0, 2).'/'.$p->sha512;
        $dl = $this->call('GET', $url, [], [], [], ['HTTP_AUTHORIZATION' => 'Basic '.base64_encode('qlts-agent:test-secret'), 'HTTP_GLPI_AGENT_ID' => Str::uuid()->toString()]);
        $dl->assertOk();
        $this->assertSame('FAKE-MSI-CONTENT', file_get_contents($dl->baseResponse->getFile()->getPathname()));

        $this->agentPost('/agent/deploy', ['action' => 'setStatus', 'machineid' => 'PC-DEP', 'part' => 'job', 'uuid' => $job->uuid, 'status' => 'ok', 'currentStep' => 'end', 'msg' => 'done'])->assertOk();

        $this->assertSame('ok', InvDeployTarget::first()->state);
    }

    public function test_failed_status_marks_target_failed(): void
    {
        $job = app(DeployService::class)->createJob($this->package(), 'asset', [$this->asset->id], null);

        $this->agentPost('/agent/deploy', ['action' => 'setStatus', 'machineid' => 'PC-DEP', 'part' => 'job', 'uuid' => $job->uuid, 'status' => 'ko', 'msg' => 'exit 1603'])->assertOk();

        $this->assertSame('failed', InvDeployTarget::first()->state);
    }

    public function test_deploy_is_closed_without_https(): void
    {
        $job = app(DeployService::class)->createJob($this->package(), 'asset', [$this->asset->id], null);
        config(['inventory.deploy_allow_http' => false]); // test dung HTTP thuong

        $this->agentPost('/agent/deploy', ['action' => 'getJobs', 'machineid' => 'PC-DEP'])->assertOk()->assertExactJson([]);
        $r = $this->agentPost('/agent/inventory', ['action' => 'getConfig', 'machineid' => 'PC-DEP'])->assertOk();
        $this->assertSame([], $r->json('schedule'));
        $this->assertSame('pending', InvDeployTarget::first()->state);
    }

    public function test_get_config_returns_deploy_schedule_when_open(): void
    {
        $r = $this->agentPost('/agent/inventory', ['action' => 'getConfig', 'machineid' => 'PC-DEP'])->assertOk();

        $this->assertSame('Deploy', $r->json('schedule.0.task'));
        $this->assertStringEndsWith('/agent/deploy', $r->json('schedule.0.remote'));
    }

    public function test_ui_requires_remote_deploy_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('inventory.deploy'))->assertForbidden();

        $admin = User::factory()->create(['permissions' => json_encode(['remote.deploy' => '1'])]);
        $this->actingAs($admin)->get(route('inventory.deploy'))->assertOk();
        $this->actingAs($admin)->get(route('inventory.packages'))->assertOk();
    }
}
