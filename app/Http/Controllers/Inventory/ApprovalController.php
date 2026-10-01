<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Inventory\InvChange;
use App\Models\Inventory\InvRemovedPart;
use App\Models\Inventory\InvTagLocation;
use App\Services\Inventory\Changes\ChangeApplier;
use App\Services\Inventory\Changes\TagLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hàng chờ duyệt dùng chung cho thay linh kiện (§8.1) và nhãn khoa phòng (§8.2).
 */
class ApprovalController extends Controller
{
    public function index(): View
    {
        $this->authorize('update', Asset::class);

        return view('inventory.approvals', [
            'changes' => InvChange::with('asset')->where('state', 'pending')->orderByDesc('severity')->orderBy('id')->get(),
            'tags' => InvTagLocation::where('state', 'pending')->orderBy('tag')->get(),
        ]);
    }

    public function approve(InvChange $change, ChangeApplier $applier): RedirectResponse
    {
        $this->authorize('update', Asset::class);
        $applier->approve($change, auth()->user());

        return redirect()->route('inventory.approvals')->with('success', trans('admin/inventory/approvals.approved'));
    }

    public function reject(Request $request, InvChange $change, ChangeApplier $applier): RedirectResponse
    {
        $this->authorize('update', Asset::class);
        $applier->reject($change, auth()->user(), $request->input('note'));

        return redirect()->route('inventory.approvals')->with('success', trans('admin/inventory/approvals.rejected'));
    }

    public function assignTag(Request $request, InvTagLocation $tag, TagLocationService $service): RedirectResponse
    {
        $this->authorize('update', Asset::class);

        if ($request->filled('new_location_name')) {
            $service->createLocationAndAssign($tag, (string) $request->input('new_location_name'), auth()->user());
        } else {
            $request->validate(['location_id' => 'required|integer|exists:locations,id']);
            $service->assign($tag, (int) $request->input('location_id'), auth()->user());
        }

        return redirect()->route('inventory.approvals')->with('success', trans('admin/inventory/approvals.approved'));
    }

    public function ignoreTag(InvTagLocation $tag, TagLocationService $service): RedirectResponse
    {
        $this->authorize('update', Asset::class);
        $service->ignore($tag, auth()->user());

        return redirect()->route('inventory.approvals')->with('success', trans('admin/inventory/approvals.rejected'));
    }

    public function removedParts(): View
    {
        $this->authorize('view', Asset::class);

        return view('inventory.removed-parts', [
            'parts' => InvRemovedPart::with('asset')->orderByDesc('id')->get(),
        ]);
    }
}
