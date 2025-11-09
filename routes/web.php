<?php

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
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/tenants/manage', [TenantController::class, 'index'])->name('tenants.manage');
    Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
    Route::post('/tenants/select', TenantSelectionController::class)->name('tenants.select');
    Route::get('/tenant/pages/{page}', [TenantPageController::class, 'show'])->name('tenants.pages.show');
    Route::get('/tenants/{tenant}/contacts', [TenantContactController::class, 'index'])->name('tenants.contacts.index');
    Route::post('/tenants/{tenant}/contacts', [TenantContactController::class, 'store'])->name('tenants.contacts.store');
    Route::put('/tenants/{tenant}/contacts/{contact}', [TenantContactController::class, 'update'])->name('tenants.contacts.update');
    Route::delete('/tenants/{tenant}/contacts/{contact}', [TenantContactController::class, 'destroy'])->name('tenants.contacts.destroy');

    Route::post('/contact-roles', [ContactRoleController::class, 'store'])->name('contact-roles.store');
    Route::put('/contact-roles/{role}', [ContactRoleController::class, 'update'])->name('contact-roles.update');
    Route::delete('/contact-roles/{role}', [ContactRoleController::class, 'destroy'])->name('contact-roles.destroy');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
