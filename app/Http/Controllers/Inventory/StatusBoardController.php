<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InvAgent;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StatusBoardController extends Controller
{
    public function index(): View
    {
        Gate::authorize('inventory.view');

        $agents = InvAgent::with('latestHeartbeat')->orderBy('hostname')->get();

        return view('inventory.status', [
            'agents' => $agents,
            'online' => $agents->filter->isOnline()->count(),
            'lowDisk' => (int) config('inventory.low_disk_percent'),
        ]);
    }
}
