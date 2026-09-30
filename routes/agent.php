<?php

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
