<?php

namespace App\Services\Inventory\Deploy;

use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvDeployJob;
use App\Models\Inventory\InvDeployTarget;
use App\Models\Inventory\InvPackage;
use App\Models\User;
use App\Services\Inventory\SectionReader;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Cài app từ xa (spec §10). Chỉ chạy gói đã nạp vào kho và đã băm SHA512 (QĐ-7).
 */
class DeployService
{
    /** RB-7: không bật cài app từ xa khi chưa có HTTPS. */
    public function isOpen(Request $request): bool
    {
        return (bool) config('inventory.deploy_enabled')
            && ($request->secure() || config('inventory.deploy_allow_http'));
    }

    /**
     * Trả lời `getConfig`: lịch cho task Deploy trỏ về /agent/deploy.
     *
     * @return array<string, mixed>
     */
    public function configAnswer(Request $request): array
    {
        if (! $this->isOpen($request)) {
            return ['schedule' => []];
        }

        return ['schedule' => [['task' => 'Deploy', 'remote' => url('agent/deploy')]]];
    }

    public function storePackage(UploadedFile $file, array $data, ?User $by): InvPackage
    {
        $sha = hash_file('sha512', $file->getRealPath());
        $dir = storage_path('app/inventory-packages');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file->move($dir, $sha);

        $package = InvPackage::create([
            'name' => $data['name'], 'version' => $data['version'] ?? null, 'publisher' => $data['publisher'] ?? null,
            'file_name' => basename($file->getClientOriginalName()), 'file_path' => 'inventory-packages/'.$sha,
            'sha512' => $sha, 'filesize' => filesize($dir.DIRECTORY_SEPARATOR.$sha),
            'install_cmd' => $data['install_cmd'], 'uninstall_cmd' => $data['uninstall_cmd'] ?? null,
            'needs_reboot' => (bool) ($data['needs_reboot'] ?? false), 'ask_user' => (bool) ($data['ask_user'] ?? false),
            'created_by' => $by?->id,
        ]);

        foreach ($data['checks'] ?? [] as $c) {
            $package->checks()->create(['phase' => 'before', 'check_type' => $c['type'], 'path' => $c['path'] ?? null, 'value' => $c['value'] ?? null]);
        }

        return $package;
    }

    /**
     * @param  'asset'|'location'  $scopeType
     * @param  list<int>  $scopeIds
     */
    public function createJob(InvPackage $package, string $scopeType, array $scopeIds, ?User $by): InvDeployJob
    {
        $agents = match ($scopeType) {
            'asset' => InvAgent::whereIn('asset_id', $scopeIds)->get(),
            'location' => InvAgent::whereIn('asset_id', Asset::whereIn('location_id', $scopeIds)->pluck('id'))->get(),
            default => throw new InvalidArgumentException("scope_type không hỗ trợ: {$scopeType}"),
        };

        $job = InvDeployJob::create([
            'uuid' => (string) Str::uuid(), 'inv_package_id' => $package->id, 'created_by' => $by?->id,
            'scope_type' => $scopeType, 'scope_ids' => $scopeIds, 'state' => 'active',
            'expires_at' => now()->addDays(14),
        ]);

        foreach ($agents as $agent) {
            InvDeployTarget::create(['inv_deploy_job_id' => $job->id, 'inv_agent_id' => $agent->id, 'state' => 'pending']);
        }

        return $job;
    }

    /**
     * Trả lời `getJobs` cho 1 máy. Job PHẢI có đủ uuid, associatedFiles, actions, checks (RB-5).
     *
     * @return array<string, mixed>
     */
    public function jobsFor(InvAgent $agent): array
    {
        $targets = InvDeployTarget::with('job.package.checks')
            ->where('inv_agent_id', $agent->id)->whereIn('state', ['pending', 'running'])
            ->whereHas('job', fn ($q) => $q->where('state', 'active')->where('expires_at', '>', now()))
            ->get();

        if ($targets->isEmpty()) {
            return [];
        }

        $jobs = [];
        $files = [];
        foreach ($targets as $t) {
            $p = $t->job->package;
            $jobs[] = [
                'uuid' => $t->job->uuid,
                'associatedFiles' => [$p->sha512],
                'actions' => [['cmd' => ['exec' => $p->install_cmd]]],
                'checks' => $p->checks->where('phase', 'before')->map(fn ($c) => array_filter(
                    ['type' => $c->check_type, 'path' => $c->path, 'value' => $c->value], fn ($v) => $v !== null
                ))->values()->all(),
            ];
            $files[$p->sha512] = [
                'name' => $p->file_name, 'filesize' => $p->filesize, 'multiparts' => [$p->sha512],
                'mirrors' => [url('agent/deploy/file').'/'], 'p2p' => 1, 'p2p-retention-duration' => 24, 'uncompress' => 0,
            ];
            $t->update(['state' => 'running', 'started_at' => $t->started_at ?? now()]);
        }

        return ['jobs' => $jobs, 'associatedFiles' => $files];
    }

    /**
     * Agent báo tiến độ (Job.pm setStatus). Kết thúc: part=job + currentStep=end + ok, hoặc status=ko.
     *
     * @param  array<string, mixed>  $m
     */
    public function setStatus(InvAgent $agent, array $m): void
    {
        $uuid = (string) SectionReader::value($m, 'uuid');
        $target = InvDeployTarget::where('inv_agent_id', $agent->id)
            ->whereHas('job', fn ($q) => $q->where('uuid', $uuid))->first();
        if ($target === null) {
            return;
        }

        $status = strtolower((string) SectionReader::value($m, 'status'));
        $part = SectionReader::value($m, 'part');
        $line = now()->format('H:i:s').' ['.($part ?? '?').'/'.SectionReader::value($m, 'currentstep').'] '.$status.' '.SectionReader::value($m, 'msg');
        $update = ['log' => trim($target->log."\n".$line)];

        if ($status === 'ko') {
            $update += ['state' => 'failed', 'finished_at' => now()];
        } elseif ($part === 'job' && $status === 'ok' && SectionReader::value($m, 'currentstep') === 'end') {
            $update += ['state' => 'ok', 'finished_at' => now()];
        }

        $target->update($update);
    }
}
