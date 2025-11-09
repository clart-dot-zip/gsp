<?php

use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Admin\TenantApiKeyController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactRoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantContactController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantPageController;
use App\Http\Controllers\TenantSelectionController;
use App\Http\Controllers\TenantPermissions\TenantGroupController;
use App\Http\Controllers\TenantPermissions\TenantGroupPermissionController;
use App\Http\Controllers\TenantPermissions\TenantPermissionController;
use App\Http\Controllers\TenantPermissions\TenantPlayerController;
use App\Http\Controllers\TenantPermissions\TenantPlayerGroupController;
use App\Http\Controllers\TenantSupport\TenantSupportTicketAttachmentController;
use App\Http\Controllers\TenantSupport\TenantSupportTicketController;
use App\Http\Controllers\TenantSupport\TenantSupportTicketNoteController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login/authentik', [AuthController::class, 'redirect'])->name('login.authentik');
    Route::get('/auth/callback', [AuthController::class, 'callback']);
    Route::get('/login/steam', [AuthController::class, 'redirectToSteam'])->name('login.steam');
    Route::get('/auth/steam/callback', [AuthController::class, 'handleSteamCallback'])->name('login.steam.callback');
});

Route::middleware(['auth', 'tenant.activity'])->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('permission:view_dashboard');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard')->middleware('permission:view_dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tenants/manage', [TenantController::class, 'index'])->name('tenants.manage')->middleware('permission:manage_tenants');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store')->middleware('permission:manage_tenants');
    Route::post('/tenants/select', TenantSelectionController::class)->name('tenants.select')->middleware('permission:manage_tenants');
    Route::get('/tenant/pages/{page}', [TenantPageController::class, 'show'])->name('tenants.pages.show')->middleware('permission:view_tenant_pages');
    Route::get('/tenants/{tenant}/contacts', [TenantContactController::class, 'index'])->name('tenants.contacts.index')->middleware('permission:manage_contacts');
    Route::post('/tenants/{tenant}/contacts', [TenantContactController::class, 'store'])->name('tenants.contacts.store')->middleware('permission:manage_contacts');
    Route::put('/tenants/{tenant}/contacts/{contact}', [TenantContactController::class, 'update'])->name('tenants.contacts.update')->middleware('permission:manage_contacts');
    Route::delete('/tenants/{tenant}/contacts/{contact}', [TenantContactController::class, 'destroy'])->name('tenants.contacts.destroy')->middleware('permission:manage_contacts');

    Route::scopeBindings()->group(function () {
        Route::prefix('/tenants/{tenant}/permissions')->name('tenants.permissions.')->middleware('permission:manage_tenant_permissions')->group(function () {
            Route::get('/groups/{group}', [TenantGroupController::class, 'edit'])->name('groups.edit');
            Route::post('/groups', [TenantGroupController::class, 'store'])->name('groups.store');
            Route::put('/groups/{group}', [TenantGroupController::class, 'update'])->name('groups.update');
            Route::delete('/groups/{group}', [TenantGroupController::class, 'destroy'])->name('groups.destroy');

            Route::post('/groups/{group}/permissions', [TenantGroupPermissionController::class, 'sync'])->name('groups.permissions.sync');

            Route::get('/definitions/{permission}', [TenantPermissionController::class, 'edit'])->name('definitions.edit');
            Route::post('/definitions', [TenantPermissionController::class, 'store'])->name('definitions.store');
            Route::put('/definitions/{permission}', [TenantPermissionController::class, 'update'])->name('definitions.update');
            Route::delete('/definitions/{permission}', [TenantPermissionController::class, 'destroy'])->name('definitions.destroy');

            Route::get('/players/{player}', [TenantPlayerController::class, 'edit'])->name('players.edit');
            Route::post('/players', [TenantPlayerController::class, 'store'])->name('players.store');
            Route::put('/players/{player}', [TenantPlayerController::class, 'update'])->name('players.update');
            Route::delete('/players/{player}', [TenantPlayerController::class, 'destroy'])->name('players.destroy');

            Route::post('/players/{player}/groups', [TenantPlayerGroupController::class, 'attach'])->name('players.groups.attach');
            Route::delete('/players/{player}/groups/{group}', [TenantPlayerGroupController::class, 'detach'])->name('players.groups.detach');
        });

        Route::prefix('/tenants/{tenant}/support')->name('tenants.support.')->middleware('permission:view_tenant_pages')->group(function () {
            Route::post('/tickets', [TenantSupportTicketController::class, 'store'])->name('tickets.store');
            Route::put('/tickets/{ticket}', [TenantSupportTicketController::class, 'update'])->name('tickets.update');
            Route::post('/tickets/{ticket}/claim', [TenantSupportTicketController::class, 'claim'])->name('tickets.claim');
            Route::delete('/tickets/{ticket}/claim', [TenantSupportTicketController::class, 'release'])->name('tickets.release');

            Route::post('/tickets/{ticket}/notes', [TenantSupportTicketNoteController::class, 'store'])->name('tickets.notes.store');
            Route::delete('/tickets/{ticket}/notes/{note}', [TenantSupportTicketNoteController::class, 'destroy'])->name('tickets.notes.destroy');

            Route::delete('/tickets/{ticket}/attachments/{attachment}', [TenantSupportTicketAttachmentController::class, 'destroy'])->name('tickets.attachments.destroy');
        });
    });

    Route::post('/contact-roles', [ContactRoleController::class, 'store'])->name('contact-roles.store')->middleware('permission:manage_contacts');
    Route::put('/contact-roles/{role}', [ContactRoleController::class, 'update'])->name('contact-roles.update')->middleware('permission:manage_contacts');
    Route::delete('/contact-roles/{role}', [ContactRoleController::class, 'destroy'])->name('contact-roles.destroy')->middleware('permission:manage_contacts');

    Route::get('/admin/access', [AccessController::class, 'index'])->name('admin.access.index')->middleware('permission:manage_access');
    Route::post('/admin/access/users/{user}/groups', [AccessController::class, 'attachGroup'])->name('admin.access.users.groups.attach')->middleware('permission:manage_access');
    Route::delete('/admin/access/users/{user}/groups/{group}', [AccessController::class, 'detachGroup'])->name('admin.access.users.groups.detach')->middleware('permission:manage_access');
    Route::post('/admin/access/groups', [AccessController::class, 'storeGroup'])->name('admin.access.groups.store')->middleware('permission:manage_access');
    Route::put('/admin/access/groups/{group}/permissions', [AccessController::class, 'syncPermissions'])->name('admin.access.groups.permissions.sync')->middleware('permission:manage_access');

    Route::get('/admin/tenants/api-keys', [TenantApiKeyController::class, 'index'])->name('admin.tenants.api-keys.index')->middleware('permission:manage_api_keys');
    Route::post('/admin/tenants/{tenant}/api-keys', [TenantApiKeyController::class, 'store'])->name('admin.tenants.api-keys.store')->middleware('permission:manage_api_keys');
    Route::delete('/admin/tenants/{tenant}/api-keys', [TenantApiKeyController::class, 'destroy'])->name('admin.tenants.api-keys.destroy')->middleware('permission:manage_api_keys');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
