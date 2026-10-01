<?php

use App\Http\Controllers\Agent\DeployAgentController;
use App\Http\Controllers\Agent\HeartbeatController;
use App\Http\Controllers\Agent\InventoryIngestController;
use Illuminate\Support\Facades\Route;

/*
 * Routes for the agent on workstations.
 *
 * Registered OUTSIDE the 'web' middleware group, so there is no session and no
 * CSRF (VerifyCsrfToken only lives in the 'web' group, see Kernel.php:76).
 * The middleware is referenced by CLASS NAME rather than an alias, so
 * $middlewareAliases in Kernel.php stays untouched.
 */
Route::post('inventory', [InventoryIngestController::class, 'store'])
    ->name('agent.inventory');

Route::post('deploy', [DeployAgentController::class, 'handle'])->name('agent.deploy');
Route::get('deploy/file/{a}/{ab}/{sha512}', [DeployAgentController::class, 'file'])->name('agent.deploy.file');

Route::post('heartbeat', [HeartbeatController::class, 'store'])
    ->name('agent.heartbeat');
