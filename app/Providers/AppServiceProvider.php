<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('authentik', \SocialiteProviders\Authentik\Provider::class);
        });
        // Share Google Maps API key with all views
        View::share('googleMapsApiKey', env('GOOGLE_MAPS_API_KEY'));

        View::composer([
            'layouts.*',
            'dashboard',
            'tenants.*',
        ], function ($view) {
            $view->with('tenantPages', Config::get('tenant.pages'));

            if (! Schema::hasTable('tenants') || ! Auth::check()) {
                $view->with('availableTenants', Collection::make());
                $view->with('currentTenant', null);

                return;
            }

            $tenants = Tenant::orderBy('name')->get();
            $selectedTenantId = (int) Session::get('tenant_id');
            $currentTenant = $tenants->firstWhere('id', $selectedTenantId);

            $view->with('availableTenants', $tenants);
            $view->with('currentTenant', $currentTenant);
        });
    }
}
