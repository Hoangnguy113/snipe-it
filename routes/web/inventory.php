<?php

use App\Http\Controllers\Inventory\CatalogController;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

Route::group(['prefix' => 'inventory', 'middleware' => ['auth']], function () {
    Route::get('catalog', [CatalogController::class, 'index'])
        ->name('inventory.catalog')
        ->breadcrumbs(fn (Trail $trail) => $trail
            ->parent('home')
            ->push(trans('admin/inventory/catalog.title'), route('inventory.catalog'))
        );
});
