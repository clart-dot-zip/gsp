<?php

use App\Http\Controllers\Api\TenantDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant.api')
    ->prefix('v1')
    ->group(function () {
        Route::get('/tenant', [TenantDataController::class, 'tenant']);
        Route::get('/tenant/contacts', [TenantDataController::class, 'contacts']);
        Route::get('/tenant/groups', [TenantDataController::class, 'groups']);
        Route::get('/tenant/permissions', [TenantDataController::class, 'permissions']);
        Route::get('/tenant/logs', [TenantDataController::class, 'logs']);
        Route::post('/tenant/logs', [TenantDataController::class, 'storeLog']);
    });
