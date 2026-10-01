<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvRemoteSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RemoteControlController extends Controller
{
    /**
     * Mọi lần bấm đều ghi nhật ký (§9.2) rồi chuyển sang RustDesk Client.
     * Không bao giờ nhúng mật khẩu vào link (§12.4).
     */
    public function start(Request $request, Asset $asset): RedirectResponse
    {
        Gate::authorize('remote.control');

        $user = $request->user();
        if (config('inventory.remote_require_2fa') && ! $user->two_factor_active_and_enrolled()) {
            return redirect()->back()->with('error', trans('admin/inventory/remote.need_2fa'));
        }

        $agent = InvAgent::where('asset_id', $asset->id)->whereNotNull('rustdesk_id')->latest('last_inventory_at')->first();
        if ($agent === null) {
            return redirect()->back()->with('error', trans('admin/inventory/remote.no_id'));
        }

        InvRemoteSession::create([
            'user_id' => $user->id, 'asset_id' => $asset->id, 'inv_agent_id' => $agent->id,
            'rustdesk_id' => $agent->rustdesk_id, 'started_at' => now(), 'operator_ip' => $request->ip(),
            'note' => $request->input('note'),
        ]);

        return redirect()->away('rustdesk://'.rawurlencode($agent->rustdesk_id));
    }

    public function log(Request $request): View
    {
        Gate::authorize('remote.control');

        $q = InvRemoteSession::with(['user', 'asset'])->orderByDesc('started_at');
        $q->when($request->filled('user_id'), fn ($x) => $x->where('user_id', $request->integer('user_id')));
        $q->when($request->filled('asset_id'), fn ($x) => $x->where('asset_id', $request->integer('asset_id')));
        $q->when($request->filled('from'), fn ($x) => $x->where('started_at', '>=', $request->date('from')->startOfDay()));
        $q->when($request->filled('to'), fn ($x) => $x->where('started_at', '<=', $request->date('to')->endOfDay()));

        return view('inventory.remote-log', ['sessions' => $q->paginate(50)->withQueryString()]);
    }
}
