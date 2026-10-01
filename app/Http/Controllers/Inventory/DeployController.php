<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvDeployJob;
use App\Models\Inventory\InvPackage;
use App\Models\Location;
use App\Services\Inventory\Deploy\DeployService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DeployController extends Controller
{
    public function packages(): View
    {
        Gate::authorize('remote.deploy');

        return view('inventory.packages', ['packages' => InvPackage::orderByDesc('id')->get()]);
    }

    public function storePackage(Request $request, DeployService $deploy): RedirectResponse
    {
        Gate::authorize('remote.deploy');

        $data = $request->validate([
            'name' => 'required|string|max:100', 'version' => 'nullable|string|max:60', 'publisher' => 'nullable|string|max:100',
            'install_cmd' => 'required|string', 'uninstall_cmd' => 'nullable|string', 'package' => 'required|file',
            'needs_reboot' => 'boolean', 'ask_user' => 'boolean',
        ]);
        $deploy->storePackage($request->file('package'), $data, $request->user());

        return redirect()->route('inventory.packages')->with('success', trans('admin/inventory/deploy.package_saved'));
    }

    public function jobs(): View
    {
        Gate::authorize('remote.deploy');

        return view('inventory.deploy-jobs', [
            'jobs' => InvDeployJob::with(['package', 'targets.agent'])->orderByDesc('id')->get(),
            'packages' => InvPackage::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(['id', 'name']),
            'enabled' => $this->isEnabled(),
        ]);
    }

    public function storeJob(Request $request, DeployService $deploy): RedirectResponse
    {
        Gate::authorize('remote.deploy');

        $data = $request->validate([
            'package_id' => 'required|exists:inv_packages,id',
            'scope_type' => 'required|in:asset,location',
            'scope_ids' => 'required|string',
        ]);
        $ids = array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', $data['scope_ids']))));
        $job = $deploy->createJob(InvPackage::findOrFail($data['package_id']), $data['scope_type'], $ids, $request->user());

        return redirect()->route('inventory.deploy')->with('success', trans('admin/inventory/deploy.job_created', ['count' => $job->targets()->count()]));
    }

    private function isEnabled(): bool
    {
        return (bool) config('inventory.deploy_enabled') && (request()->secure() || config('inventory.deploy_allow_http'));
    }
}
