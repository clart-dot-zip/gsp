<?php

use App\Http\Controllers\Api\TenantDataController;
use App\Http\Controllers\Api\TenantGroupController;
use App\Http\Controllers\Api\TenantPermissionController;
use App\Http\Controllers\Api\TenantPlayerController;
use Illuminate\Support\Facades\Route;

Route::middleware('tenant.api')
    ->prefix('v1')
    ->group(function () {
        Route::get('/tenant', [TenantDataController::class, 'tenant']);
        Route::get('/tenant/contacts', [TenantDataController::class, 'contacts']);
        Route::get('/tenant/groups', [TenantGroupController::class, 'index']);
        Route::post('/tenant/groups', [TenantGroupController::class, 'store']);
        Route::get('/tenant/groups/{group}', [TenantGroupController::class, 'show']);
        Route::put('/tenant/groups/{group}', [TenantGroupController::class, 'update']);
        Route::delete('/tenant/groups/{group}', [TenantGroupController::class, 'destroy']);

        Route::get('/tenant/permissions', [TenantPermissionController::class, 'index']);
        Route::post('/tenant/permissions', [TenantPermissionController::class, 'store']);
        Route::get('/tenant/permissions/{permission}', [TenantPermissionController::class, 'show']);
        Route::put('/tenant/permissions/{permission}', [TenantPermissionController::class, 'update']);
        Route::delete('/tenant/permissions/{permission}', [TenantPermissionController::class, 'destroy']);

        Route::get('/tenant/players', [TenantPlayerController::class, 'index']);
        Route::post('/tenant/players', [TenantPlayerController::class, 'store']);
        Route::get('/tenant/players/{player}', [TenantPlayerController::class, 'show']);
        Route::put('/tenant/players/{player}', [TenantPlayerController::class, 'update']);
        Route::delete('/tenant/players/{player}', [TenantPlayerController::class, 'destroy']);
        Route::get('/tenant/logs', [TenantDataController::class, 'logs']);
        Route::post('/tenant/logs', [TenantDataController::class, 'storeLog']);
    });
