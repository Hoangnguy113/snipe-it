<?php

use App\Http\Controllers\Inventory\ApprovalController;
use App\Http\Controllers\Inventory\CatalogController;
use App\Http\Controllers\Inventory\DeployController;
use App\Http\Controllers\Inventory\InventoryReportController;
use App\Http\Controllers\Inventory\RemoteControlController;
use App\Http\Controllers\Inventory\StatusBoardController;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

Route::group(['prefix' => 'inventory', 'middleware' => ['auth']], function () {
    Route::get('catalog', [CatalogController::class, 'index'])
        ->name('inventory.catalog')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/catalog.title'), route('inventory.catalog'))
        );

    Route::get('approvals', [ApprovalController::class, 'index'])
        ->name('inventory.approvals')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/approvals.title'), route('inventory.approvals'))
        );

    Route::post('approvals/changes/{change}/approve', [ApprovalController::class, 'approve'])->name('inventory.changes.approve');
    Route::post('approvals/changes/{change}/reject', [ApprovalController::class, 'reject'])->name('inventory.changes.reject');
    Route::post('approvals/tags/{tag}/assign', [ApprovalController::class, 'assignTag'])->name('inventory.tags.assign');
    Route::post('approvals/tags/{tag}/ignore', [ApprovalController::class, 'ignoreTag'])->name('inventory.tags.ignore');

    Route::post('remote/{asset}', [RemoteControlController::class, 'start'])->name('inventory.remote.start');
    Route::get('remote-log', [RemoteControlController::class, 'log'])
        ->name('inventory.remote.log')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/remote.log_title'), route('inventory.remote.log'))
        );

    Route::get('status', [StatusBoardController::class, 'index'])
        ->name('inventory.status')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/status.title'), route('inventory.status'))
        );

    Route::get('packages', [DeployController::class, 'packages'])
        ->name('inventory.packages')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/deploy.packages_title'), route('inventory.packages'))
        );
    Route::post('packages', [DeployController::class, 'storePackage'])->name('inventory.packages.store');
    Route::get('deploy', [DeployController::class, 'jobs'])
        ->name('inventory.deploy')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/deploy.jobs_title'), route('inventory.deploy'))
        );
    Route::post('deploy', [DeployController::class, 'storeJob'])->name('inventory.deploy.store');

    Route::get('reports/{key}', [InventoryReportController::class, 'show'])
        ->where('key', 'changes|lost|software|health')
        ->name('inventory.reports');

    Route::get('removed-parts', [ApprovalController::class, 'removedParts'])
        ->name('inventory.removed_parts')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/approvals.removed_title'), route('inventory.removed_parts'))
        );
});
