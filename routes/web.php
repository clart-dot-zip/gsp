<?php

use App\Http\Controllers\Admin\AccessController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactRoleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantContactController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\TenantPageController;
use App\Http\Controllers\TenantSelectionController;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Socialite::driver('authentik')->redirect();
    })->name('login');
    Route::get('/auth/callback', [AuthController::class, 'callback']);
    Route::get('/login/steam', [AuthController::class, 'redirectToSteam'])->name('login.steam');
    Route::get('/auth/steam/callback', [AuthController::class, 'handleSteamCallback'])->name('login.steam.callback');
});

Route::middleware('auth')->group(function () {
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

    Route::post('/contact-roles', [ContactRoleController::class, 'store'])->name('contact-roles.store')->middleware('permission:manage_contacts');
    Route::put('/contact-roles/{role}', [ContactRoleController::class, 'update'])->name('contact-roles.update')->middleware('permission:manage_contacts');
    Route::delete('/contact-roles/{role}', [ContactRoleController::class, 'destroy'])->name('contact-roles.destroy')->middleware('permission:manage_contacts');

    Route::get('/admin/access', [AccessController::class, 'index'])->name('admin.access.index')->middleware('permission:manage_access');
    Route::post('/admin/access/users/{user}/groups', [AccessController::class, 'attachGroup'])->name('admin.access.users.groups.attach')->middleware('permission:manage_access');
    Route::delete('/admin/access/users/{user}/groups/{group}', [AccessController::class, 'detachGroup'])->name('admin.access.users.groups.detach')->middleware('permission:manage_access');
    Route::post('/admin/access/groups', [AccessController::class, 'storeGroup'])->name('admin.access.groups.store')->middleware('permission:manage_access');
    Route::put('/admin/access/groups/{group}/permissions', [AccessController::class, 'syncPermissions'])->name('admin.access.groups.permissions.sync')->middleware('permission:manage_access');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
