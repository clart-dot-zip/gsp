<?php

namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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

        if (Schema::hasTable('tenants')) {
            $tenants = Tenant::orderBy('name')->get();
            $selectedTenantId = (int) session('tenant_id');
            $currentTenant = $tenants->firstWhere('id', $selectedTenantId);

            View::share('availableTenants', $tenants);
            View::share('currentTenant', $currentTenant);
        }

        View::share('tenantPages', config('tenant.pages'));
    }
}
